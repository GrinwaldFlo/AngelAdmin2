<?php

declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\CoachPaymentListener;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

/**
 * A meeting with paid coach presences can't be deleted
 *
 * @uses \App\Event\CoachPaymentListener
 */
class CoachPaymentListenerTest extends TestCase
{
    use CoachPaymentDataTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    private int $main;

    protected function setUp(): void
    {
        parent::setUp();
        CoachPaymentListener::register();
        $this->addCoach(1, 1);
        $this->main = $this->addPresenceType(1, 'Main', 10);
    }

    public function testRegisterOnlyOnce(): void
    {
        $before = count(EventManager::instance()->listeners('Model.beforeDelete'));

        CoachPaymentListener::register();
        CoachPaymentListener::register();

        $this->assertCount($before, EventManager::instance()->listeners('Model.beforeDelete'));
    }

    public function testRegisterAgainOnANewManager(): void
    {
        EventManager::instance(new EventManager());
        $this->assertCount(0, EventManager::instance()->listeners('Model.beforeDelete'));

        CoachPaymentListener::register();

        $this->assertCount(1, EventManager::instance()->listeners('Model.beforeDelete'));
    }

    public function testMeetingWithOpenPresencesIsDeletedWithThem(): void
    {
        $meetingId = $this->addMeeting(1);
        $this->addCoachPresence($meetingId, 1, $this->main);
        $meetings = $this->getTableLocator()->get('Meetings');

        $this->assertTrue($meetings->delete($meetings->get($meetingId)));
        $this->assertSame(0, $this->getTableLocator()->get('CoachPresences')->find()->where(['meeting_id' => $meetingId])->count());
    }

    public function testMeetingWithPaidPresencesIsNotDeleted(): void
    {
        $meetingId = $this->addMeeting(1);
        $this->addCoachPresence($meetingId, 1, $this->main);
        $this->getTableLocator()->get('CoachPayments')->payOpen(1, 1);
        $meetings = $this->getTableLocator()->get('Meetings');
        $meeting = $meetings->get($meetingId);

        $this->assertFalse($meetings->delete($meeting));
        $this->assertTrue($meetings->exists(['id' => $meetingId]));
        $this->assertNotEmpty($meeting->getError('id'));
    }

    public function testOtherTablesAreNotConcerned(): void
    {
        $meetingId = $this->addMeeting(1);
        $presenceId = $this->addCoachPresence($meetingId, 1, $this->main);
        $presences = $this->getTableLocator()->get('CoachPresences');

        $this->assertTrue($presences->delete($presences->get($presenceId)));
    }
}
