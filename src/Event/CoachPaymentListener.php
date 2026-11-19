<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Meeting;
use App\Model\Entity\Member;
use App\Model\Table\CoachPresencesTable;
use App\Model\Table\MeetingsTable;
use App\Model\Table\MembersTable;
use App\Model\Table\SitesTable;
use App\Model\Table\TeamsTable;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;

/**
 * Hooks of the coach payment module in the existing tables (kept out of them)
 * - The paid history is never lost: a meeting with paid coach presences, a coach with payments
 *   and a site with coach payments or used presence types can't be deleted
 * - The site of a presence is the site of the team of its meeting: a meeting with coach presences
 *   can't move to a team of another site, a team whose meetings have coach presences can't change site
 * - The open presences of a deleted meeting or member are deleted with it (the foreign keys restrict)
 */
class CoachPaymentListener implements EventListenerInterface
{
    /**
     * Event manager the listener is attached to (the global manager is replaced between the tests)
     */
    private static ?\WeakReference $registeredOn = null;

    /**
     * Attach the listener to the global event manager, once
     */
    public static function register(): void
    {
        $manager = EventManager::instance();
        if (self::$registeredOn?->get() === $manager) {
            return;
        }
        $manager->on(new self());
        self::$registeredOn = \WeakReference::create($manager);
    }

    public function implementedEvents(): array
    {
        return [
            'Model.beforeRules' => 'beforeRules',
            'Model.beforeDelete' => 'beforeDelete',
        ];
    }

    /**
     * Rules checked on every save / delete (not added in Model.buildRules: the rules checker of a table
     * is built once, maybe before the listener was registered)
     */
    public function beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, string $operation): void
    {
        $error = $this->ruleError($event->getSubject(), $entity, $operation);
        if ($error === null) {
            return;
        }
        [$field, $rule, $message] = $error;
        $entity->setError($field, [$rule => $message]);
        $event->stopPropagation();
        $event->setResult(false);
    }

    /**
     * @return array{string, string, string}|null [error field, rule name, message], null when the rules pass
     */
    private function ruleError(object $table, EntityInterface $entity, string $operation): ?array
    {
        $delete = $operation === RulesChecker::DELETE;
        if ($table instanceof MeetingsTable && $delete) {
            return $this->presences()->exists(['meeting_id' => $entity->id, 'coach_payment_id IS NOT' => null])
                ? ['id', 'coachPaid', __('This meeting has paid coach presences, it can\'t be deleted.')] : null;
        }
        if ($table instanceof MeetingsTable) {
            return $this->meetingStaysInSite($table, $entity)
                ? null : ['team_id', 'coachPresencesInSite', __('This meeting has coach presences, it can\'t move to a team of another location.')];
        }
        if ($table instanceof MembersTable && $delete) {
            return TableRegistry::getTableLocator()->get('CoachPayments')->exists(['member_id' => $entity->id])
                ? ['id', 'coachPaid', __('A coach with payments cannot be deleted.')] : null;
        }
        if ($table instanceof SitesTable && $delete) {
            return $this->siteHasCoachHistory((int)$entity->id)
                ? ['id', 'coachHistory', __('This location has coach presences or payments, it can\'t be deleted.')] : null;
        }
        if ($table instanceof TeamsTable && !$delete) {
            return $this->teamStaysInSite($entity)
                ? null : ['site_id', 'coachPresencesInSite', __('The meetings of this team have coach presences, it can\'t move to another location.')];
        }

        return null;
    }

    /**
     * The open presences are deleted with their meeting or member (the paid ones block the delete in the rules)
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof MeetingsTable && $entity instanceof Meeting) {
            $this->presences()->deleteAll(['meeting_id' => $entity->id, 'coach_payment_id IS' => null]);
        } elseif ($subject instanceof MembersTable && $entity instanceof Member) {
            $this->presences()->deleteAll(['member_id' => $entity->id, 'coach_payment_id IS' => null]);
        }
    }

    private function presences(): CoachPresencesTable
    {
        /** @var \App\Model\Table\CoachPresencesTable */
        return TableRegistry::getTableLocator()->get('CoachPresences');
    }

    private function teamStaysInSite(EntityInterface $team): bool
    {
        if ($team->isNew() || !$team->isDirty('site_id') || (int)$team->getOriginal('site_id') === (int)$team->site_id) {
            return true;
        }

        return $this->presences()->find()->innerJoinWith('Meetings')->where(['Meetings.team_id' => $team->id])->count() === 0;
    }

    private function meetingStaysInSite(MeetingsTable $meetings, EntityInterface $meeting): bool
    {
        if ($meeting->isNew() || !$meeting->isDirty('team_id') || (int)$meeting->getOriginal('team_id') === (int)$meeting->team_id) {
            return true;
        }
        if (!$this->presences()->exists(['meeting_id' => $meeting->id])) {
            return true;
        }
        $sites = $meetings->Teams->find()
            ->select(['site_id'])
            ->where(['id IN' => [(int)$meeting->getOriginal('team_id'), (int)$meeting->team_id]])
            ->all()
            ->extract('site_id')
            ->toList();

        return count(array_unique($sites)) === 1;
    }

    private function siteHasCoachHistory(int $siteId): bool
    {
        return TableRegistry::getTableLocator()->get('CoachPayments')->exists(['site_id' => $siteId])
            || $this->presences()->find()->innerJoinWith('CoachPresenceTypes')->where(['CoachPresenceTypes.site_id' => $siteId])->count() > 0;
    }
}
