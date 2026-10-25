<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Entity\Presence;
use App\Model\Table\MeetingsTable;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\MeetingsController Test Case
 *
 * @uses \App\Controller\MeetingsController
 */
class MeetingsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Meetings',
        'app.Teams',
        'app.Presences',
        'app.Members',
        'app.Sites',
        'app.Users',
        'app.Roles',
        'app.TeamsMembers',
        'app.Configurations',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
    }

    private function login(array $session = []): void
    {
        // Texts are checked in English, whatever translations exist
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en'], ['id' => 1]);
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)] + $session);
    }

    private function removeRight(string $right): void
    {
        $this->getTableLocator()->get('Roles')->updateAll([$right => 0], ['id' => 1]);
    }

    private function addMeeting(string $date, string $name = 'Training', int $teamId = 1): int
    {
        $meetings = $this->getTableLocator()->get('Meetings');
        $meeting = $meetings->saveOrFail($meetings->newEntity(['meeting_date' => new DateTime($date), 'name' => $name, 'team_id' => $teamId]));
        return $meeting->id;
    }

    private function addTeam(int $id, string $name): void
    {
        $teams = $this->getTableLocator()->get('Teams');
        $teams->saveOrFail($teams->newEntity(['id' => $id, 'name' => $name, 'membership_fee' => 1, 'active' => 1, 'site_id' => 1, 'description' => '']));
    }

    public function testIndexRequiresLogin(): void
    {
        $this->get('/meetings');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexSelectsTheOnlyTeam(): void
    {
        $this->login();
        $this->get('/meetings');

        $this->assertResponseOk();
        $this->assertSame(1, $this->viewVariable('teamId'));
        $this->assertSame(MeetingsTable::FILTER_UPCOMING, $this->viewVariable('meetingFilter'));
        $this->assertSame([], $this->viewVariable('meetings'));
        $this->assertResponseContains('No upcoming meeting');
        $this->assertSession(1, 'teamId');
    }

    public function testIndexAsksForATeam(): void
    {
        $this->addTeam(2, 'Second team');
        $this->login();
        $this->get('/meetings');

        $this->assertResponseOk();
        $this->assertSame(0, $this->viewVariable('teamId'));
        $this->assertResponseContains('mt-team-grid');
        $this->assertResponseContains('Second team');
        $this->assertResponseNotContains('mt-tabs');
    }

    public function testIndexIgnoresTeamOfAnotherSite(): void
    {
        $this->addTeam(2, 'Second team');
        $this->getTableLocator()->get('Sites')->insertQuery()
            ->insert(['id', 'city', 'feeMax', 'sender_email', 'sender'])
            ->values(['id' => 2, 'city' => 'Second city', 'feeMax' => 1, 'sender_email' => 'second@example.com', 'sender' => 'Second'])
            ->execute();
        $this->getTableLocator()->get('Teams')->updateAll(['site_id' => 2], ['id' => 2]);
        $this->getTableLocator()->get('Members')->updateAll(['pref_site_id' => 1], ['id' => 1]);
        $this->login();
        $this->get('/meetings/index/2');

        $this->assertResponseOk();
        // Team 2 is not in the site list: falls back to the only remaining team
        $this->assertSame(1, $this->viewVariable('teamId'));
    }

    public function testIndexPast(): void
    {
        $this->addMeeting('2020-04-01 18:00', 'Second training');
        $this->login();
        $this->get('/meetings/index/1/' . MeetingsTable::FILTER_PAST);

        $this->assertResponseOk();
        $meetings = $this->viewVariable('meetings');
        $this->assertCount(2, $meetings);
        // Most recent first
        $this->assertSame('Second training', $meetings[0]->name);
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertFalse($this->viewVariable('hasMore'));
    }

    public function testIndexUpcomingWithToday(): void
    {
        $this->addMeeting((new DateTime('today'))->format('Y-m-d') . ' 00:01', 'Training of today');
        $this->addMeeting('+10 days', 'Next training');
        $this->login();
        $this->get('/meetings/index/1/' . MeetingsTable::FILTER_UPCOMING);

        $this->assertResponseOk();
        $this->assertCount(2, $this->viewVariable('meetings'));
        $this->assertResponseContains('mt-today');
        $this->assertResponseContains('Take attendance');
        $this->assertResponseContains('Next training');
    }

    public function testIndexOldTodayFilterBecomesUpcoming(): void
    {
        $this->login(['meetingFilter' => MeetingsTable::FILTER_TODAY]);
        $this->get('/meetings/index/1');

        $this->assertSame(MeetingsTable::FILTER_UPCOMING, $this->viewVariable('meetingFilter'));
    }

    public function testIndexShowMore(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->addMeeting('+' . $i . ' days');
        }
        $this->login();
        $this->get('/meetings/index/1/' . MeetingsTable::FILTER_UPCOMING);

        $this->assertCount(20, $this->viewVariable('meetings'));
        $this->assertTrue($this->viewVariable('hasMore'));
        $this->assertResponseContains('n=40');

        $this->get('/meetings/index/1/' . MeetingsTable::FILTER_UPCOMING . '?n=40');
        $this->assertCount(21, $this->viewVariable('meetings'));
        $this->assertFalse($this->viewVariable('hasMore'));
    }

    public function testIndexAddButtonNeedsEditRight(): void
    {
        $this->login();
        $this->get('/meetings');
        $this->assertResponseContains('mt-fab');

        $this->removeRight('MemberEditAll');
        $this->get('/meetings');
        $this->assertResponseOk();
        $this->assertResponseNotContains('mt-fab');
    }

    public function testView(): void
    {
        $this->login();
        $this->get('/meetings/view/1');

        $this->assertResponseOk();
        $attendance = $this->viewVariable('attendance');
        $this->assertCount(1, $attendance);
        $this->assertSame(1, $attendance[0]['id']);
        $this->assertSame(Presence::PRESENT, $attendance[0]['state']);
        $this->assertTrue($attendance[0]['inTeam']);
        $this->assertResponseContains('data-csrf=');
        $this->assertResponseContains('/presences/set-presence/1');
        $this->assertResponseContains('aria-pressed="true"');
        $this->assertResponseContains('/meetings/edit/1');
        $this->assertSession(1, 'teamId');
    }

    public function testViewListsTeamMembersAndFormerMembers(): void
    {
        $members = $this->getTableLocator()->get('Members');
        $member = $members->get(1)->toArray();
        foreach ([2 => 'Zoe', 3 => 'Anna'] as $id => $firstName) {
            $copy = $members->newEntity(['first_name' => $firstName, 'last_name' => 'Test'] + $member, ['validate' => false, 'accessibleFields' => ['id' => true]]);
            $copy->id = $id;
            $copy->hash = str_repeat((string)$id, 40);
            $members->saveOrFail($copy, ['checkRules' => false]);
        }
        // Anna is in the team without status, Zoe left the team but was absent
        $this->getTableLocator()->get('TeamsMembers')->insertQuery()->insert(['team_id', 'member_id'])->values(['team_id' => 1, 'member_id' => 3])->execute();
        $presences = $this->getTableLocator()->get('Presences');
        $presences->saveOrFail($presences->newEntity(['meeting_id' => 1, 'member_id' => 2, 'state' => Presence::ABSENT]));

        $this->login();
        $this->get('/meetings/view/1');

        $this->assertResponseOk();
        $attendance = $this->viewVariable('attendance');
        $this->assertSame([3, 1, 2], array_column($attendance, 'id'));
        $this->assertSame([Presence::NONE, Presence::PRESENT, Presence::ABSENT], array_column($attendance, 'state'));
        $this->assertSame([true, true, false], array_column($attendance, 'inTeam'));
        $this->assertResponseContains('Not in the team anymore');
    }

    public function testViewNeighbours(): void
    {
        $this->addTeam(2, 'Second team');
        $nextId = $this->addMeeting('2020-04-05 18:00', 'Next one');
        $this->addMeeting('2020-04-05 18:00', 'Other team', 2);
        $this->login();
        $this->get('/meetings/view/1');

        $neighbours = $this->viewVariable('neighbours');
        $this->assertNull($neighbours['prev']);
        $this->assertSame($nextId, $neighbours['next']->id);

        $this->get('/meetings/view/' . $nextId);
        $this->assertSame(1, $this->viewVariable('neighbours')['prev']->id);
        $this->assertNull($this->viewVariable('neighbours')['next']);
    }

    public function testViewHidesEditActionsWithoutRight(): void
    {
        $this->removeRight('MemberEditAll');
        $this->login();
        $this->get('/meetings/view/1');

        $this->assertResponseOk();
        $this->assertResponseNotContains('/meetings/edit/1');
        $this->assertResponseNotContains('/meetings/delete/1');
    }

    public function testViewForbiddenWithoutViewRight(): void
    {
        $this->removeRight('MemberViewAll');
        $this->login();
        $this->get('/meetings/view/1');

        $this->assertResponseCode(403);
    }

    public function testAddRedirectsToAddMultiple(): void
    {
        $this->login();
        $this->get('/meetings/add');
        $this->assertRedirectContains('/meetings/add-multiple');
    }

    public function testAddMultipleForm(): void
    {
        $this->login(['teamId' => 1]);
        $this->get('/meetings/add-multiple');

        $this->assertResponseOk();
        $this->assertSame(1, $this->viewVariable('meeting')->team_id);
        $this->assertResponseContains('mt-add-form');
        $this->assertResponseContains('Create {0} meetings');
    }

    public function testAddMultipleWeekly(): void
    {
        $this->enableCsrfToken();
        $this->login();
        $this->post('/meetings/add-multiple', [
            'mode' => 'weekly',
            'team_id' => 1,
            'name' => 'Weekly training',
            'time' => '18:30',
            'big_event' => '0',
            'doodle' => '1',
            'url' => '',
            'dates' => ['2030-01-10', '2030-01-03', '2030-01-03', 'not a date'],
        ]);

        $this->assertRedirect(['controller' => 'Meetings', 'action' => 'index', 1, MeetingsTable::FILTER_UPCOMING]);
        $this->assertFlashMessage('2 meetings have been saved.');
        $created = $this->getTableLocator()->get('Meetings')->find()->where(['name' => 'Weekly training'])->orderBy(['meeting_date' => 'ASC'])->toArray();
        $this->assertCount(2, $created);
        // The date is exactly the one chosen, without time zone shift
        $this->assertSame('2030-01-03 18:30', $created[0]->meeting_date->format('Y-m-d H:i'));
        $this->assertSame('2030-01-10 18:30', $created[1]->meeting_date->format('Y-m-d H:i'));
        $this->assertTrue($created[0]->doodle);
    }

    public function testAddMultipleOnce(): void
    {
        $this->enableCsrfToken();
        $this->login();
        $this->post('/meetings/add-multiple', [
            'mode' => 'once',
            'team_id' => 1,
            'name' => 'Gala',
            'time' => '14:00',
            'big_event' => '1',
            'date' => '2030-06-01',
            'dates' => ['2030-06-08'],
        ]);

        $this->assertFlashMessage('1 meeting has been saved.');
        $created = $this->getTableLocator()->get('Meetings')->find()->where(['name' => 'Gala'])->toArray();
        $this->assertCount(1, $created);
        $this->assertSame('2030-06-01 14:00', $created[0]->meeting_date->format('Y-m-d H:i'));
        $this->assertTrue($created[0]->big_event);
    }

    public function testAddMultipleWithoutDate(): void
    {
        $this->enableCsrfToken();
        $this->login();
        $this->post('/meetings/add-multiple', ['mode' => 'weekly', 'team_id' => 1, 'name' => 'Nothing', 'time' => '18:00']);

        $this->assertResponseOk();
        $this->assertResponseContains('alert-danger');
        $this->assertSame(0, $this->getTableLocator()->get('Meetings')->find()->where(['name' => 'Nothing'])->count());
    }

    public function testAddMultipleWithoutName(): void
    {
        $this->enableCsrfToken();
        $this->login();
        $this->post('/meetings/add-multiple', ['mode' => 'weekly', 'team_id' => 1, 'name' => '', 'time' => '18:00', 'dates' => ['2030-01-03']]);

        $this->assertResponseOk();
        $this->assertResponseContains('alert-danger');
        $this->assertResponseContains('is-invalid');
        $this->assertTrue($this->viewVariable('meeting')->hasErrors());
        $this->assertSame(1, $this->getTableLocator()->get('Meetings')->find()->count());
    }

    public function testAddMultipleForbiddenWithoutEditRight(): void
    {
        $this->removeRight('MemberEditAll');
        $this->login();
        $this->get('/meetings/add-multiple');

        $this->assertResponseCode(403);
    }

    public function testEdit(): void
    {
        $this->enableCsrfToken();
        $this->login();
        $this->post('/meetings/edit/1', ['meeting_date' => '2020-03-30T19:15', 'team_id' => 1, 'name' => 'Renamed', 'big_event' => '0', 'doodle' => '0', 'url' => '']);

        $this->assertRedirect(['controller' => 'Meetings', 'action' => 'view', 1]);
        $meeting = $this->getTableLocator()->get('Meetings')->get(1);
        $this->assertSame('Renamed', $meeting->name);
        $this->assertSame('2020-03-30 19:15', $meeting->meeting_date->format('Y-m-d H:i'));
    }

    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->login();
        $this->post('/meetings/delete/1');

        $this->assertRedirect(['controller' => 'Meetings', 'action' => 'index', 1]);
        $this->assertSame(0, $this->getTableLocator()->get('Meetings')->find()->count());
    }
}
