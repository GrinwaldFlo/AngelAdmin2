<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\CoachPresence;
use App\Model\Entity\Meeting;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use DateTimeInterface;

/**
 * Presence type of the coaches at the meetings
 * The site of a presence is the site of the team of its meeting
 *
 * @property \App\Model\Table\MeetingsTable&\Cake\ORM\Association\BelongsTo $Meetings
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 * @property \App\Model\Table\CoachPresenceTypesTable&\Cake\ORM\Association\BelongsTo $CoachPresenceTypes
 * @property \App\Model\Table\CoachPaymentsTable&\Cake\ORM\Association\BelongsTo $CoachPayments
 */
class CoachPresencesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('coach_presences');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Meetings', [
            'foreignKey' => 'meeting_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('CoachPresenceTypes', [
            'foreignKey' => 'coach_presence_type_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('CoachPayments', [
            'foreignKey' => 'coach_payment_id',
        ]);
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['meeting_id'], 'Meetings'), ['errorField' => 'meeting_id']);
        $rules->add($rules->existsIn(['member_id'], 'Members'), ['errorField' => 'member_id']);
        $rules->add($rules->existsIn(['coach_presence_type_id'], 'CoachPresenceTypes'), ['errorField' => 'coach_presence_type_id']);
        $rules->add($rules->isUnique(['meeting_id', 'member_id']), ['errorField' => 'member_id']);

        return $rules;
    }

    /**
     * Coach rows of the attendance page: current coaches of the team, then the other members declared at this meeting
     * @return array<int, array{member: \App\Model\Entity\Member, coachType: int|null, presence: \App\Model\Entity\CoachPresence|null}> by member id
     */
    public function rowsOfMeeting(Meeting $meeting): array
    {
        $presences = $this->find()
            ->contain(['Members'])
            ->where(['CoachPresences.meeting_id' => $meeting->id])
            ->all()
            ->indexBy('member_id')
            ->toArray();

        $rows = [];
        foreach (TableRegistry::getTableLocator()->get('TeamsCoaches')->rowsByTeam([$meeting->team_id])[$meeting->team_id] as $teamCoach) {
            $rows[$teamCoach->member_id] = [
                'member' => $teamCoach->member,
                'coachType' => $teamCoach->type,
                'presence' => $presences[$teamCoach->member_id] ?? null,
            ];
        }
        foreach ($presences as $memberId => $presence) {
            $rows[$memberId] ??= ['member' => $presence->member, 'coachType' => null, 'presence' => $presence];
        }

        return $rows;
    }

    /**
     * Set the presence type of a coach at a meeting
     * @param int|null $typeId null = not declared (the row is removed)
     * @return string|null error message, null when saved
     */
    public function setType(Meeting $meeting, int $memberId, ?int $typeId): ?string
    {
        $rows = $this->rowsOfMeeting($meeting);
        if (!isset($rows[$memberId])) {
            return __('This member is not a coach of the team.');
        }
        $presence = $rows[$memberId]['presence'];
        if ($presence?->isPaid()) {
            return $this->paidError();
        }

        // An existing presence is changed only while it is still open (conditions on the row, not on the
        // entity read above): a payment taking it at the same time wins, the change is then refused
        if ($typeId === null) {
            if ($presence === null || $this->deleteAll(['id' => $presence->id, 'coach_payment_id IS' => null]) > 0) {
                return null;
            }

            return $this->isPaidNow($presence->id) ? $this->paidError() : null;
        }

        $siteId = (int)$this->Meetings->Teams->get($meeting->team_id)->site_id;
        $typeExists = $this->CoachPresenceTypes->exists(['id' => $typeId, 'site_id' => $siteId, 'active' => true]);
        if (!$typeExists) {
            return __('This presence type can\'t be used for this meeting.');
        }

        $sameType = $presence !== null && (int)$presence->coach_presence_type_id === $typeId;
        if (!$sameType && $this->isTypeFull($meeting, $memberId, $typeId)) {
            return __('The maximum number of coaches with this presence type is reached for this meeting.');
        }

        if ($presence === null) {
            $presence = $this->newEmptyEntity();
            $presence->meeting_id = $meeting->id;
            $presence->member_id = $memberId;
            $presence->coach_presence_type_id = $typeId;

            return $this->save($presence) ? null : __('The presence could not be saved.');
        }
        if ($sameType) {
            return null;
        }
        $changed = $this->updateAll(
            ['coach_presence_type_id' => $typeId, 'modified' => DateTime::now()],
            ['id' => $presence->id, 'coach_payment_id IS' => null],
        );

        return $changed > 0 || !$this->isPaidNow($presence->id) ? null : $this->paidError();
    }

    /**
     * The limit of the type for the team of the meeting is reached by the other coaches (paid or not)
     */
    private function isTypeFull(Meeting $meeting, int $memberId, int $typeId): bool
    {
        $max = TableRegistry::getTableLocator()->get('CoachPresenceLimits')->limitsOfTeam($meeting->team_id)[$typeId] ?? null;
        if ($max === null) {
            return false;
        }

        return ($this->typeCounts($meeting->id, $memberId)[$typeId] ?? 0) >= $max;
    }

    /**
     * Number of coaches by presence type at a meeting
     * @param int|null $exceptMemberId coach not counted (the one being changed)
     * @return array<int, int> [type id => count]
     */
    public function typeCounts(int $meetingId, ?int $exceptMemberId = null): array
    {
        $query = $this->find()->select(['coach_presence_type_id'])->where(['meeting_id' => $meetingId]);
        if ($exceptMemberId !== null) {
            $query->where(['member_id !=' => $exceptMemberId]);
        }

        return $query->all()->countBy(fn ($presence) => $presence->coach_presence_type_id)->toArray();
    }

    private function isPaidNow(int $presenceId): bool
    {
        return $this->exists(['id' => $presenceId, 'coach_payment_id IS NOT' => null]);
    }

    private function paidError(): string
    {
        return __('This presence is already paid, it can\'t be changed.');
    }

    /**
     * Presences of the meetings of a site, with their meeting, team and type, oldest first
     * @param int|null $memberId null = all coaches
     */
    public function findOfSite(SelectQuery $query, int $siteId, ?int $memberId = null): SelectQuery
    {
        $query->contain(['Meetings.Teams', 'CoachPresenceTypes'])
            ->where(['Teams.site_id' => $siteId])
            ->orderBy(['Meetings.meeting_date' => 'ASC', 'CoachPresences.id' => 'ASC']);
        if ($memberId !== null) {
            $query->where(['CoachPresences.member_id' => $memberId]);
        }

        return $query;
    }

    /**
     * Open presences (not paid) of a site
     * @param bool|null $due true = meeting date reached, false = planned (future meeting), null = both
     */
    public function findOpen(SelectQuery $query, int $siteId, ?int $memberId = null, ?bool $due = null): SelectQuery
    {
        $query = $this->findOfSite($query, $siteId, $memberId)->where(['CoachPresences.coach_payment_id IS' => null]);
        if ($due !== null) {
            $query->where([($due ? 'Meetings.meeting_date <=' : 'Meetings.meeting_date >') => DateTime::now()]);
        }

        return $query;
    }

    /**
     * Open presences of a site by coach
     * @return array<int, array{due: \App\Model\Entity\CoachPresence[], planned: \App\Model\Entity\CoachPresence[], dueTotal: float, plannedTotal: float}> by member id
     */
    public function balances(int $siteId, ?int $memberId = null): array
    {
        $result = [];
        foreach ($this->find('open', siteId: $siteId, memberId: $memberId) as $presence) {
            $result[$presence->member_id] ??= ['due' => [], 'planned' => [], 'dueTotal' => 0.0, 'plannedTotal' => 0.0];
            $key = $presence->isDue() ? 'due' : 'planned';
            $result[$presence->member_id][$key][] = $presence;
            $result[$presence->member_id][$key . 'Total'] += $presence->value();
        }

        return $result;
    }

    /**
     * Number of presences by coach, team and type, from a date to now (paid or not)
     * @return array<int, array<string, array<int, int>>> [member id => [team name => [type id => count]]]
     */
    public function statistics(int $siteId, DateTimeInterface $from, ?int $memberId = null): array
    {
        $query = $this->find('ofSite', siteId: $siteId, memberId: $memberId)
            ->where(['Meetings.meeting_date >=' => $from, 'Meetings.meeting_date <=' => DateTime::now()]);
        $result = [];
        foreach ($query as $presence) {
            $team = $presence->meeting->team->name;
            $result[$presence->member_id][$team][$presence->coach_presence_type_id] ??= 0;
            $result[$presence->member_id][$team][$presence->coach_presence_type_id]++;
        }
        foreach ($result as &$teams) {
            ksort($teams);
        }

        return $result;
    }

    public function hasPaidForMeeting(int $meetingId): bool
    {
        return $this->exists(['meeting_id' => $meetingId, 'coach_payment_id IS NOT' => null]);
    }
}
