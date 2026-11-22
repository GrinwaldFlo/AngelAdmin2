<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\TeamsCoach;
use App\Model\Table\CoachPresencesTable;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * Coach presences: setting the type from the attendance page, balances and statistics
 *
 * Site 1: team 1 coached by member 1 (coach) and member 100 (help coach), types Main 10.- and Secondary 5.-
 * Site 2: team 5, type Other 7.-
 *
 * @uses \App\Model\Table\CoachPresencesTable
 */
class CoachPresencesTableTest extends TestCase
{
    use CoachPaymentDataTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    protected CoachPresencesTable $CoachPresences;

    private int $main;
    private int $secondary;
    private int $otherSite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CoachPresences = $this->getTableLocator()->get('CoachPresences');

        $this->addSite(2);
        $this->addTeam(5, 'Other city', true, 2);
        $this->addMember(100, [1]);
        $this->addMember(101, [1]);
        $this->addCoach(1, 1);
        $this->addCoach(1, 100, TeamsCoach::HELP_COACH);
        $this->main = $this->addPresenceType(1, 'Main', 10);
        $this->secondary = $this->addPresenceType(1, 'Secondary', 5);
        $this->otherSite = $this->addPresenceType(2, 'Other', 7);
    }

    private function meeting(string $date = '-2 days', int $teamId = 1): \App\Model\Entity\Meeting
    {
        return $this->getTableLocator()->get('Meetings')->get($this->addMeeting($teamId, $date));
    }

    private function typeOf(int $meetingId, int $memberId): ?int
    {
        return $this->CoachPresences->find()->where(['meeting_id' => $meetingId, 'member_id' => $memberId])->first()?->coach_presence_type_id;
    }

    public function testRowsOfMeetingListsTheCoachesOfTheTeam(): void
    {
        $meeting = $this->meeting();

        $rows = $this->CoachPresences->rowsOfMeeting($meeting);

        $this->assertSame([1, 100], array_keys($rows));
        $this->assertSame(TeamsCoach::COACH, $rows[1]['coachType']);
        $this->assertSame(TeamsCoach::HELP_COACH, $rows[100]['coachType']);
        $this->assertNull($rows[1]['presence']);
    }

    public function testRowsOfMeetingKeepsADeclaredCoachRemovedFromTheTeam(): void
    {
        $meeting = $this->meeting();
        $this->addCoachPresence($meeting->id, 101, $this->main);

        $rows = $this->CoachPresences->rowsOfMeeting($meeting);

        $this->assertSame([1, 100, 101], array_keys($rows));
        $this->assertNull($rows[101]['coachType']);
        $this->assertSame($this->main, $rows[101]['presence']->coach_presence_type_id);
    }

    public function testSetTypeCreatesChangesAndClears(): void
    {
        $meeting = $this->meeting();

        $this->assertNull($this->CoachPresences->setType($meeting, 100, $this->main));
        $this->assertSame($this->main, $this->typeOf($meeting->id, 100));

        $this->assertNull($this->CoachPresences->setType($meeting, 100, $this->secondary));
        $this->assertSame($this->secondary, $this->typeOf($meeting->id, 100));
        $this->assertSame(1, $this->CoachPresences->find()->count());

        $this->assertNull($this->CoachPresences->setType($meeting, 100, null));
        $this->assertNull($this->typeOf($meeting->id, 100));
    }

    public function testSetTypeAllowedForAFutureMeeting(): void
    {
        $meeting = $this->meeting('+7 days');

        $this->assertNull($this->CoachPresences->setType($meeting, 1, $this->main));
        $this->assertSame($this->main, $this->typeOf($meeting->id, 1));
    }

    public function testSetTypeRefusedForAMemberWhoIsNotCoach(): void
    {
        $meeting = $this->meeting();

        $this->assertNotNull($this->CoachPresences->setType($meeting, 101, $this->main));
        $this->assertNull($this->typeOf($meeting->id, 101));
    }

    public function testSetTypeRefusedForATypeOfAnotherSite(): void
    {
        $meeting = $this->meeting();

        $this->assertNotNull($this->CoachPresences->setType($meeting, 1, $this->otherSite));
        $this->assertNull($this->typeOf($meeting->id, 1));
    }

    public function testSetTypeRefusedForAnInactiveType(): void
    {
        $meeting = $this->meeting();
        $inactive = $this->addPresenceType(1, 'Old', 3, false);

        $this->assertNotNull($this->CoachPresences->setType($meeting, 1, $inactive));
        $this->assertNull($this->typeOf($meeting->id, 1));
    }

    public function testSetTypeRefusedForAnUnknownType(): void
    {
        $meeting = $this->meeting();

        $this->assertNotNull($this->CoachPresences->setType($meeting, 1, 999));
    }

    public function testSetTypeRefusedWhenPaid(): void
    {
        $meeting = $this->meeting();
        $this->addCoachPresence($meeting->id, 1, $this->main);
        $this->getTableLocator()->get('CoachPayments')->payOpen(1, 1);

        $this->assertNotNull($this->CoachPresences->setType($meeting, 1, $this->secondary));
        $this->assertNotNull($this->CoachPresences->setType($meeting, 1, null));
        $this->assertSame($this->main, $this->typeOf($meeting->id, 1));
    }

    public function testSetTypeRefusedWhenTheLimitIsReached(): void
    {
        $meeting = $this->meeting();
        $this->addPresenceLimit(1, $this->main, 1);

        $this->assertNull($this->CoachPresences->setType($meeting, 1, $this->main));
        $this->assertNotNull($this->CoachPresences->setType($meeting, 100, $this->main));
        $this->assertNull($this->typeOf($meeting->id, 100));
        // The other types have no limit
        $this->assertNull($this->CoachPresences->setType($meeting, 100, $this->secondary));
        // Changing the type of a coach to a full type is refused too
        $this->assertNotNull($this->CoachPresences->setType($meeting, 100, $this->main));
        $this->assertSame($this->secondary, $this->typeOf($meeting->id, 100));
    }

    public function testLimitFreedWhenACoachIsCleared(): void
    {
        $meeting = $this->meeting();
        $this->addPresenceLimit(1, $this->main, 1);
        $this->CoachPresences->setType($meeting, 1, $this->main);

        $this->assertNull($this->CoachPresences->setType($meeting, 1, null));
        $this->assertNull($this->CoachPresences->setType($meeting, 100, $this->main));
        $this->assertSame($this->main, $this->typeOf($meeting->id, 100));
    }

    public function testLimitCountsOnlyTheMeeting(): void
    {
        $this->addPresenceLimit(1, $this->main, 1);
        $first = $this->meeting('-2 days');
        $second = $this->meeting('-1 day');
        $this->CoachPresences->setType($first, 1, $this->main);

        $this->assertNull($this->CoachPresences->setType($second, 100, $this->main));
    }

    public function testLimitCountsThePaidPresences(): void
    {
        $meeting = $this->meeting();
        $this->addPresenceLimit(1, $this->main, 1);
        $this->addCoachPresence($meeting->id, 1, $this->main);
        $this->getTableLocator()->get('CoachPayments')->payOpen(1, 1);

        $this->assertNotNull($this->CoachPresences->setType($meeting, 100, $this->main));
    }

    public function testLimitZeroForbidsTheType(): void
    {
        $meeting = $this->meeting();
        $this->addPresenceLimit(1, $this->main, 0);

        $this->assertNotNull($this->CoachPresences->setType($meeting, 1, $this->main));
        $this->assertNull($this->typeOf($meeting->id, 1));
    }

    public function testLimitLoweredKeepsTheExistingPresences(): void
    {
        $meeting = $this->meeting();
        $this->addCoachPresence($meeting->id, 1, $this->main);
        $this->addCoachPresence($meeting->id, 100, $this->main);
        $this->addPresenceLimit(1, $this->main, 1);

        // Setting the same type again is not a new presence
        $this->assertNull($this->CoachPresences->setType($meeting, 100, $this->main));
        $this->assertSame($this->main, $this->typeOf($meeting->id, 100));
        $this->assertSame([$this->main => 2], $this->CoachPresences->typeCounts($meeting->id));
        $this->assertSame([$this->main => 1], $this->CoachPresences->typeCounts($meeting->id, 100));
    }

    /**
     * A payment takes the presence after setType() read it (simulated when the coaches of the team are read, after the presences)
     */
    private function paidAfterRead(int $presenceId): void
    {
        $paid = false;
        $this->getTableLocator()->get('TeamsCoaches')->getEventManager()->on('Model.beforeFind', function () use ($presenceId, &$paid) {
            if (!$paid) {
                $paid = true;
                $this->CoachPresences->updateAll(['coach_payment_id' => $this->addCoachPayment(1, 1), 'amount' => 10], ['id' => $presenceId]);
            }
        });
    }

    public function testSetTypeRefusedWhenPaidMeanwhile(): void
    {
        $meeting = $this->meeting();
        $this->paidAfterRead($this->addCoachPresence($meeting->id, 1, $this->main));

        $this->assertNotNull($this->CoachPresences->setType($meeting, 1, $this->secondary));
        $this->assertSame($this->main, $this->typeOf($meeting->id, 1));
    }

    public function testClearRefusedWhenPaidMeanwhile(): void
    {
        $meeting = $this->meeting();
        $this->paidAfterRead($this->addCoachPresence($meeting->id, 1, $this->main));

        $this->assertNotNull($this->CoachPresences->setType($meeting, 1, null));
        $this->assertSame($this->main, $this->typeOf($meeting->id, 1));
    }

    public function testSetTheSameTypeAgain(): void
    {
        $meeting = $this->meeting();
        $this->addCoachPresence($meeting->id, 1, $this->main);

        $this->assertNull($this->CoachPresences->setType($meeting, 1, $this->main));
        $this->assertSame($this->main, $this->typeOf($meeting->id, 1));
    }

    public function testBalancesSeparateDueAndPlanned(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-3 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->secondary);
        $this->addCoachPresence($this->addMeeting(1, '+2 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 100, $this->secondary);

        $balances = $this->CoachPresences->balances(1);

        $this->assertSame(15.0, $balances[1]['dueTotal']);
        $this->assertSame(10.0, $balances[1]['plannedTotal']);
        $this->assertCount(2, $balances[1]['due']);
        $this->assertCount(1, $balances[1]['planned']);
        $this->assertSame(5.0, $balances[100]['dueTotal']);
        $this->assertSame([1], array_keys($this->CoachPresences->balances(1, 1)));
    }

    public function testBalancesOnlyForTheSite(): void
    {
        $this->addCoach(5, 1);
        $this->addCoachPresence($this->addMeeting(1), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(5), 1, $this->otherSite);

        $this->assertSame(10.0, $this->CoachPresences->balances(1)[1]['dueTotal']);
        $this->assertSame(7.0, $this->CoachPresences->balances(2)[1]['dueTotal']);
    }

    public function testOpenBalanceFollowsTheTypeAmountButPaidAmountDoesNot(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-3 days'), 1, $this->main);
        $this->getTableLocator()->get('CoachPayments')->payOpen(1, 1);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->main);

        $types = $this->getTableLocator()->get('CoachPresenceTypes');
        $types->updateAll(['amount' => 12], ['id' => $this->main]);

        $this->assertSame(12.0, $this->CoachPresences->balances(1)[1]['dueTotal']);
        $paid = $this->CoachPresences->find()->contain(['CoachPresenceTypes'])->where(['coach_payment_id IS NOT' => null])->firstOrFail();
        $this->assertSame(10.0, $paid->value());
    }

    public function testStatisticsCountsByTeamAndTypeFromTheDateUntilNow(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-3 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-1 days'), 1, $this->secondary);
        $this->addCoachPresence($this->addMeeting(1, '-40 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '+1 days'), 1, $this->main);

        $statistics = $this->CoachPresences->statistics(1, new DateTime('-10 days'));

        $team = $this->getTableLocator()->get('Teams')->get(1)->name;
        $this->assertSame([1 => [$team => [$this->main => 2, $this->secondary => 1]]], $statistics);
    }

    public function testHasPaidForMeeting(): void
    {
        $meetingId = $this->addMeeting(1);
        $this->addCoachPresence($meetingId, 1, $this->main);
        $this->assertFalse($this->CoachPresences->hasPaidForMeeting($meetingId));

        $this->getTableLocator()->get('CoachPayments')->payOpen(1, 1);
        $this->assertTrue($this->CoachPresences->hasPaidForMeeting($meetingId));
    }
}
