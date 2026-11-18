<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Meeting;
use App\Model\Table\MeetingsTable;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;

/**
 * Hooks of the coach payment module in the existing tables (kept out of them)
 * - A meeting with paid coach presences can't be deleted: the paid history would be lost
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
        return ['Model.beforeDelete' => 'beforeDelete'];
    }

    public function beforeDelete(EventInterface $event, EntityInterface $entity): void
    {
        if (!$event->getSubject() instanceof MeetingsTable || !$entity instanceof Meeting) {
            return;
        }
        if (TableRegistry::getTableLocator()->get('CoachPresences')->hasPaidForMeeting((int)$entity->id)) {
            $entity->setError('id', ['coachPaid' => __('This meeting has paid coach presences, it can\'t be deleted.')]);
            $event->stopPropagation();
            $event->setResult(false);
        }
    }
}
