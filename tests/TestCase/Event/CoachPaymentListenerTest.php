<?php

declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\CoachPaymentListener;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

/**
 * The paid history of the coaches is never lost: delete and site change guards
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
        $this->addSite(2);
        $this->addTeam(5, 'Other city', true, 2);
        $this->addTeam(6, 'Same city', true, 1);
        $this->addMember(100);
        $this->addCoach(1, 1);
        $this->addCoach(1, 100);
        $this->main = $this->addPresenceType(1, 'Main', 10);
    }

    private function presenceCount(array $conditions): int
    {
        return $this->getTableLocator()->get('CoachPresences')->find()->where($conditions)->count();
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

    public function testCoachWithPaymentsIsNotDeleted(): void
    {
        $this->addCoachPresence($this->addMeeting(1), 100, $this->main);
        $this->getTableLocator()->get('CoachPayments')->payOpen(100, 1);
        $members = $this->getTableLocator()->get('Members');
        $member = $members->get(100);

        $this->assertFalse($members->delete($member));
        $this->assertTrue($members->exists(['id' => 100]));
        $this->assertNotEmpty($member->getError('id'));
    }

    public function testCoachWithOpenPresencesIsDeletedWithThem(): void
    {
        $this->addCoachPresence($this->addMeeting(1), 100, $this->main);
        $members = $this->getTableLocator()->get('Members');

        $this->assertTrue($members->delete($members->get(100)));
        $this->assertSame(0, $this->presenceCount(['member_id' => 100]));
    }

    public function testSiteWithCoachPaymentsIsNotDeleted(): void
    {
        $other = $this->addPresenceType(2, 'Other', 7);
        $this->addCoach(5, 100);
        $this->addCoachPresence($this->addMeeting(5), 100, $other);
        $sites = $this->getTableLocator()->get('Sites');
        $site = $sites->get(2);

        $this->assertFalse($sites->delete($site));
        $this->assertNotEmpty($site->getError('id'));
    }

    public function testMeetingWithCoachPresencesMovesOnlyInItsSite(): void
    {
        $meetingId = $this->addMeeting(1);
        $this->addCoachPresence($meetingId, 1, $this->main);
        $meetings = $this->getTableLocator()->get('Meetings');

        $meeting = $meetings->patchEntity($meetings->get($meetingId), ['team_id' => 5]);
        $this->assertFalse($meetings->save($meeting));
        $this->assertNotEmpty($meeting->getError('team_id'));

        $meeting = $meetings->patchEntity($meetings->get($meetingId), ['team_id' => 6]);
        $this->assertNotFalse($meetings->save($meeting));
    }

    public function testMeetingWithoutCoachPresencesMovesToAnotherSite(): void
    {
        $meetings = $this->getTableLocator()->get('Meetings');
        $meeting = $meetings->patchEntity($meetings->get($this->addMeeting(1)), ['team_id' => 5]);

        $this->assertNotFalse($meetings->save($meeting));
    }

    public function testTeamWithCoachPresencesDoesNotChangeSite(): void
    {
        $this->addCoachPresence($this->addMeeting(6), 1, $this->main);
        $teams = $this->getTableLocator()->get('Teams');
        $team = $teams->get(6);
        $team->site_id = 2;

        $this->assertFalse($teams->save($team));
        $this->assertNotEmpty($team->getError('site_id'));
    }
}
