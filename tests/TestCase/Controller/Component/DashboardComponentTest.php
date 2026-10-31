<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\AppController;
use App\Controller\Component\DashboardComponent;
use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;

/**
 * Site 1: team 1 (fixture, member 1) and team 2. Site 2: team 3.
 *
 * @uses \App\Controller\Component\DashboardComponent
 */
class DashboardComponentTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.Registrations', 'app.Meetings', 'app.Presences',
    ];

    private DashboardComponent $Dashboard;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->getTableLocator()->get('Sites')->insertQuery()
            ->insert(['id', 'city', 'feeMax', 'sender_email', 'sender'])
            ->values(['id' => 2, 'city' => 'Second', 'feeMax' => 1, 'sender_email' => 's2@example.com', 'sender' => 'Second'])
            ->execute();
        $this->addTeam(2, 'B team');
        $this->addTeam(3, 'Other site', true, 2);
        $this->addTeam(4, 'Old', false);
        foreach (['Bills', 'Registrations', 'Meetings', 'Presences'] as $table) {
            $this->getTableLocator()->get($table)->deleteAll([]);
        }
        // Texts are checked in English
        $this->getTableLocator()->get('Members')->updateAll(['date_birth' => null, 'language' => 'en'], ['id' => 1]);

        $controller = $this->controller(AppController::class, $this->identity(['Admin']));
        $this->Dashboard = new DashboardComponent($controller->components());
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function birth(int $years, int $days = 0): string
    {
        return Date::today()->subYears($years)->addDays($days)->format('Y-m-d');
    }

    public function testAttendanceRate(): void
    {
        $this->assertNull(DashboardComponent::attendanceRate([]));
        $this->assertNull(DashboardComponent::attendanceRate([0 => 0, 1 => 0]));
        // Present and late count as present
        $this->assertSame(75, DashboardComponent::attendanceRate([0 => 1, 1 => 2, 3 => 1]));
        $this->assertSame(0, DashboardComponent::attendanceRate([0 => 3, 2 => 1]));
    }

    public function testTeams(): void
    {
        $names = fn (int $siteId) => array_map(fn ($team) => $team->id, $this->Dashboard->teams($siteId));

        $this->assertSame([2, 1, 3], $names(0));
        $this->assertSame([2, 1], $names(1));
        $this->assertSame([3], $names(2));
    }

    public function testMembersCounts(): void
    {
        $seasonStart = new \DateTime('2026-09-01');
        $this->getTableLocator()->get('Members')->updateAll(['registered' => 1, 'membership_fee_paid' => 1, 'date_arrival' => '2020-01-01'], ['id' => 1]);
        $this->addMember(100, [2], ['registered' => 0, 'membership_fee_paid' => 0, 'date_arrival' => '2026-09-15']);
        $this->addMember(101, [3], ['registered' => 1, 'date_arrival' => '2026-10-01']);
        $this->addMember(102, [2], ['active' => false, 'date_fin' => '2026-09-10']);
        $this->addMember(103, [], ['registered' => 1]);
        $this->getTableLocator()->get('Registrations')->insertQuery()
            ->insert(['member_id', 'year', 'validation_id'])
            ->values(['member_id' => 1, 'year' => 2026, 'validation_id' => 3])
            ->values(['member_id' => 100, 'year' => 2026, 'validation_id' => 0])
            ->values(['member_id' => 101, 'year' => 2026, 'validation_id' => 0])
            ->values(['member_id' => 1, 'year' => 2025, 'validation_id' => 0])
            ->execute();

        $site = $this->Dashboard->members(1, $seasonStart, 2026);
        $this->assertSame(2, $site['active']);
        $this->assertSame(1, $site['registered']);
        $this->assertSame(1, $site['feesPaid']);
        $this->assertSame(1, $site['new']);
        $this->assertSame(1, $site['left']);
        $this->assertSame(1, $site['signValidated']);
        $this->assertSame(1, $site['signPending']);
        $this->assertNull($site['withoutTeam']);

        $all = $this->Dashboard->members(0, $seasonStart, 2026);
        $this->assertSame(4, $all['active']);
        $this->assertSame(2, $all['new']);
        $this->assertSame(2, $all['signPending']);
        $this->assertSame(1, $all['withoutTeam']);
    }

    public function testMembersGendersAndAges(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['gender_id' => 0], ['id' => 1]);
        $this->addMember(100, [1], ['gender_id' => 1, 'date_birth' => $this->birth(7, -1)]);
        $this->addMember(101, [1], ['gender_id' => 1, 'date_birth' => $this->birth(8)]);
        $this->addMember(102, [1], ['gender_id' => 2, 'date_birth' => $this->birth(17, -1)]);
        $this->addMember(103, [1], ['gender_id' => 9, 'date_birth' => $this->birth(40)]);

        $members = $this->Dashboard->members(0, new \DateTime('2026-09-01'), 2026);

        // Unexpected gender ids are counted as unknown too
        $this->assertEquals(['Unknown' => 2, 'Female' => 2, 'Male' => 1], $members['genders']);
        $this->assertSame(['0-7' => 1, '8-11' => 1, '12-15' => 0, '16-17' => 1, '18+' => 1, 'Unknown' => 1], $members['ages']);
    }

    public function testMembersBirthdays(): void
    {
        $this->addMember(100, [1], ['first_name' => 'Later', 'date_birth' => $this->birth(10, 5)]);
        $this->addMember(101, [1], ['first_name' => 'Today', 'date_birth' => $this->birth(12)]);
        $this->addMember(102, [1], ['first_name' => 'Too late', 'date_birth' => $this->birth(10, 8)]);
        $this->addMember(103, [1], ['first_name' => 'Yesterday', 'date_birth' => $this->birth(10, -1)]);

        $birthdays = $this->Dashboard->members(0, new \DateTime('2026-09-01'), 2026)['birthdays'];

        $this->assertSame(['Today', 'Later'], array_map(fn ($row) => $row['member']->first_name, $birthdays));
        $this->assertSame(12, $birthdays[0]['age']);
        $this->assertEquals(new \DateTime('today'), $birthdays[0]['date']);
        $this->assertSame(DashboardComponent::BIRTHDAY_DAYS, $this->Dashboard->members(0, new \DateTime(), 2026)['birthdayDays']);
    }

    public function testFinances(): void
    {
        $this->addMember(100, [3]);
        $this->addBill(10, 1, ['amount' => 100, 'due_date' => '2099-01-01']);
        $this->addBill(11, 1, ['amount' => 50, 'due_date' => '2020-01-01', 'reminder' => 1, 'printed' => true]);
        $this->addBill(12, 1, ['amount' => 30, 'paid' => true]);
        $this->addBill(13, 1, ['amount' => 1000, 'canceled' => true]);
        $this->addBill(14, 100, ['amount' => 70, 'site_id' => 2]);

        $finances = $this->Dashboard->finances(1, new \DateTime('2000-01-01'));

        $this->assertSame(2, $finances['openCount']);
        $this->assertSame(150, $finances['openSum']);
        $this->assertSame(1, $finances['lateCount']);
        $this->assertSame(50, $finances['lateSum']);
        $this->assertSame(1, $finances['reminderCount']);
        $this->assertSame(1, $finances['draftCount']);
        $this->assertSame(70, $this->Dashboard->finances(2, new \DateTime('2000-01-01'))['openSum']);
        $this->assertSame(220, $this->Dashboard->finances(0, new \DateTime('2000-01-01'))['openSum']);
    }

    public function testAttendance(): void
    {
        $meetings = $this->getTableLocator()->get('Meetings');
        $past = $meetings->saveOrFail($meetings->newEntity(['meeting_date' => new \DateTime('-2 days'), 'name' => 'Past', 'team_id' => 1]));
        $meetings->saveOrFail($meetings->newEntity(['meeting_date' => new \DateTime('+2 days'), 'name' => 'Soon', 'team_id' => 1]));
        $meetings->saveOrFail($meetings->newEntity(['meeting_date' => new \DateTime('+3 days'), 'name' => 'Other site', 'team_id' => 3]));
        $meetings->saveOrFail($meetings->newEntity(['meeting_date' => new \DateTime('+20 days'), 'name' => 'Later', 'team_id' => 1]));
        $this->addMember(100, [1]);
        $this->getTableLocator()->get('Presences')->insertQuery()
            ->insert(['meeting_id', 'member_id', 'state'])
            ->values(['meeting_id' => $past->id, 'member_id' => 1, 'state' => 1])
            ->values(['meeting_id' => $past->id, 'member_id' => 100, 'state' => 0])
            ->execute();

        $attendance = $this->Dashboard->attendance(1, new \DateTime('-1 year'));

        $this->assertSame([0 => 1, 1 => 1, 2 => 0, 3 => 0], $attendance['states']);
        $this->assertSame(50, $attendance['rate']);
        $this->assertSame([1 => 50], $attendance['teamRates']);
        $this->assertSame(['Soon'], $attendance['upcoming']->extract('name')->toList());
        $this->assertSame(1, $attendance['seasonEvents']);
        $this->assertSame(['Soon', 'Other site'], $this->Dashboard->attendance(0, new \DateTime('-1 year'))['upcoming']->extract('name')->toList());
    }
}
