<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Entity\TeamsCoach;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Access of a TeamScoped role (coach): limited to the teams found in teams_coaches
 *
 * The logged user is member 1 (user 1, role 1). Teams: 2 coached by member 1, 3 not coached.
 * Members: 100 in team 2, 101 in team 3, 102 in teams 2 and 3.
 *
 * @uses \App\Policy\TeamScope
 * @uses \App\Controller\AppController::getCoachedTeamIds()
 */
class CoachAccessTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Members',
        'app.Users',
        'app.Roles',
        'app.Sites',
        'app.Teams',
        'app.TeamsMembers',
        'app.TeamsCoaches',
        'app.Configurations',
        'app.Contents',
        'app.Meetings',
        'app.Presences',
        'app.Bills',
        'app.Mailings',
        'app.MailingItems',
        'app.Fields',
        'app.FieldTypes',
        'app.Registrations',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $this->addTeam(2, 'Juniors');
        $this->addTeam(3, 'Seniors');
        $this->addMember(100, [2]);
        $this->addMember(101, [3]);
        $this->addMember(102, [2, 3]);
        $this->addCoach(2, 1);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    /**
     * Role 1 becomes a coach role: members and bills (view), limited to the coached teams
     */
    private function loginCoach(): void
    {
        $this->getTableLocator()->get('Roles')->updateAll([
            'Admin' => 0, 'TeamScoped' => 1, 'MemberViewAll' => 1, 'MemberEditAll' => 1, 'MemberEditOwn' => 1,
            'BillViewAll' => 1, 'BillEditAll' => 0, 'BillValidate' => 0, 'Editor' => 0,
        ], ['id' => 1]);
        $this->login();
    }

    private function login(): void
    {
        // Texts are checked in English, whatever translations exist
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en'], ['id' => 1]);
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)]);
    }

    private function addTeam(int $id, string $name, bool $active = true): void
    {
        $this->getTableLocator()->get('Teams')->insertQuery()
            ->insert(['id', 'name', 'membership_fee', 'active', 'site_id'])
            ->values(['id' => $id, 'name' => $name, 'membership_fee' => 1, 'active' => $active, 'site_id' => 1])
            ->execute();
    }

    private function addMember(int $id, array $teamIds, bool $withUser = false): void
    {
        $this->getTableLocator()->get('Members')->insertQuery()
            ->insert(['id', 'first_name', 'last_name', 'gender_id', 'email', 'email_valid', 'date_arrival', 'hash', 'active', 'language'])
            ->values([
                'id' => $id, 'first_name' => 'First' . $id, 'last_name' => 'Last' . $id, 'gender_id' => 1, 'email' => "m$id@example.com",
                'email_valid' => 1, 'date_arrival' => '2026-01-01', 'hash' => sha1('member' . $id), 'active' => true, 'language' => 'en',
            ])
            ->execute();
        foreach ($teamIds as $teamId) {
            $this->getTableLocator()->get('TeamsMembers')->insertQuery()
                ->insert(['team_id', 'member_id'])
                ->values(['team_id' => $teamId, 'member_id' => $id])
                ->execute();
        }
        if ($withUser) {
            $this->getTableLocator()->get('Users')->insertQuery()
                ->insert(['id', 'username', 'password', 'role_id', 'member_id', 'pass_key', 'tokenhash'])
                ->values(['id' => $id, 'username' => 'user' . $id, 'password' => 'x', 'role_id' => 1, 'member_id' => $id, 'pass_key' => '', 'tokenhash' => ''])
                ->execute();
        }
    }

    private function addCoach(int $teamId, int $memberId, int $type = TeamsCoach::COACH): void
    {
        $this->getTableLocator()->get('TeamsCoaches')->insertQuery()
            ->insert(['team_id', 'member_id', 'type'])
            ->values(['team_id' => $teamId, 'member_id' => $memberId, 'type' => $type])
            ->execute();
    }

    private function addMeeting(int $id, int $teamId): void
    {
        $this->getTableLocator()->get('Meetings')->insertQuery()
            ->insert(['id', 'meeting_date', 'team_id', 'name'])
            ->values(['id' => $id, 'meeting_date' => '2026-11-10 18:00:00', 'team_id' => $teamId, 'name' => 'Training' . $id])
            ->execute();
    }

    /**
     * @return int mailing id
     */
    private function addMailing(array $teamIds, string $title): int
    {
        $mailings = $this->getTableLocator()->get('Mailings');
        $mailing = $mailings->newForTeams(['title' => $title, 'content' => 'Hello'], $teamIds);
        $mailings->saveOrFail($mailing);

        return $mailing->id;
    }

    // ---------------------------------------------------------------- Members

    public function testMembersIndexOnlyShowsCoachedTeams(): void
    {
        $this->loginCoach();
        $this->get('/members/index/0/1');

        $this->assertResponseOk();
        $this->assertResponseContains('First100');
        $this->assertResponseContains('First102');
        $this->assertResponseNotContains('First101');
        // Team filter: only the coached teams (Seniors is still shown as a team of member 102)
        $this->assertResponseContains('/members/index/2/1');
        $this->assertResponseNotContains('/members/index/3/1');
    }

    public function testMembersIndexOfOtherTeamFallsBackToCoachedTeams(): void
    {
        $this->loginCoach();
        $this->get('/members/index/3/1');

        $this->assertResponseOk();
        $this->assertResponseNotContains('First101');
    }

    public function testCoachWithoutTeamSeesNoMember(): void
    {
        $this->getTableLocator()->get('TeamsCoaches')->deleteAll([]);
        $this->loginCoach();
        $this->get('/members/index/0/1');

        $this->assertResponseOk();
        $this->assertResponseNotContains('First100');
        $this->assertResponseNotContains('First101');
    }

    public function testAdminWithTeamScopedSeesAllMembers(): void
    {
        $this->getTableLocator()->get('Roles')->updateAll(['TeamScoped' => 1], ['id' => 1]);
        $this->login();
        $this->get('/members/index/0/1');

        $this->assertResponseOk();
        $this->assertResponseContains('First100');
        $this->assertResponseContains('First101');
    }

    public function testMembersIndexShowsCoachLineForSelectedTeam(): void
    {
        $this->addMember(103, [], true);
        $this->addCoach(2, 103, TeamsCoach::HELP_COACH);
        $this->loginCoach();
        $this->get('/members/index/2/1');

        $this->assertResponseOk();
        $this->assertResponseContains('class="team-coaches"');
        $this->assertResponseContains('Coach:</span> Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Help coach:</span> First103 Last103');
    }

    public function testMembersIndexHidesCoachLineForAllTeams(): void
    {
        $this->loginCoach();
        $this->get('/members/index/0/1');

        $this->assertResponseOk();
        $this->assertResponseNotContains('class="team-coaches"');
    }

    public function testViewMemberOfCoachedTeam(): void
    {
        $this->loginCoach();
        $this->get('/members/view/102');

        $this->assertResponseOk();
    }

    public function testViewMemberOfOtherTeamIsForbidden(): void
    {
        $this->loginCoach();
        $this->get('/members/view/101');

        $this->assertResponseCode(403);
    }

    public function testViewOwnMemberWithoutTeam(): void
    {
        $this->getTableLocator()->get('TeamsCoaches')->deleteAll([]);
        $this->loginCoach();
        $this->get('/members/view/1');

        $this->assertResponseOk();
    }

    public function testEditMemberOfOtherTeamIsForbidden(): void
    {
        $this->loginCoach();
        $this->get('/members/edit/101');

        $this->assertResponseCode(403);
    }

    public function testEditMemberListsAllTeams(): void
    {
        $this->loginCoach();
        $this->get('/members/edit/100');

        $this->assertResponseOk();
        // A coach can move a member to any team
        $this->assertResponseContains('Seniors');
    }

    /**
     * @return array data of the add form for a new member
     */
    private function newMemberData(array $teamIds): array
    {
        return [
            'first_name' => 'New', 'last_name' => 'Member', 'date_birth' => '2010-01-01', 'gender_id' => 1,
            'address' => 'Street 1', 'postcode' => 1000, 'city' => 'City', 'phone_mobile' => '', 'phone_home' => '',
            'email' => 'new@example.com', 'email_valid' => 1, 'nationality' => 'CH', 'date_arrival' => '2026-01-01',
            'multi_payment' => 1, 'membership_fee_paid' => 0, 'discount' => 0, 'date_fin' => '', 'active' => 1,
            'registered' => 0, 'teams' => ['_ids' => $teamIds],
        ];
    }

    public function testCreateMemberOutsideCoachedTeamsWarns(): void
    {
        $this->loginCoach();
        $this->post('/members/add', $this->newMemberData([3]));

        $this->assertRedirect(['controller' => 'Members', 'action' => 'index']);
        $this->assertTrue($this->getTableLocator()->get('Members')->exists(['first_name' => 'New']));
        $this->assertFlashElementAt(1, 'flash/warning');
    }

    public function testCreateMemberInCoachedTeamDoesNotWarn(): void
    {
        $this->loginCoach();
        $this->post('/members/add', $this->newMemberData([2]));

        $this->assertRedirect(['controller' => 'Members', 'action' => 'index']);
        $this->assertFlashElement('flash/success');
        $this->assertSame(['flash/success'], array_column($this->_requestSession->read('Flash.flash'), 'element'));
    }

    public function testNewMembersIsForbidden(): void
    {
        $this->loginCoach();
        $this->get('/members/new-members');

        $this->assertResponseCode(403);
    }

    public function testSignaturesIsForbidden(): void
    {
        $this->loginCoach();
        $this->get('/members/signatures');

        $this->assertResponseCode(403);
    }

    public function testBatchRegisterOfOtherTeamMemberIsForbidden(): void
    {
        $this->loginCoach();
        $this->post('/members/batch-register', ['memberId' => 101]);

        $this->assertResponseCode(403);
    }

    public function testShopAndOrdersAreForbidden(): void
    {
        $this->loginCoach();
        $this->get('/shop-items');
        $this->assertResponseCode(403);

        $this->get('/member-orders');
        $this->assertResponseCode(403);
    }

    public function testDashboardIsForbidden(): void
    {
        $this->loginCoach();
        $this->get('/admin/dashboard');

        $this->assertRedirect('/');
    }

    public function testMenuHidesLinksRefusedToCoaches(): void
    {
        $this->loginCoach();
        $this->get('/members/index/0/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Emails to teams');
        $this->assertResponseNotContains('Signatures validation');
        $this->assertResponseNotContains('Shop items');
        $this->assertResponseNotContains('/teams/coaches');
    }

    // ---------------------------------------------------------------- Teams

    public function testTeamsIndexOnlyShowsCoachedTeams(): void
    {
        $this->loginCoach();
        $this->get('/teams');

        $this->assertResponseOk();
        $this->assertResponseContains('Juniors');
        $this->assertResponseNotContains('Seniors');
        $this->assertResponseNotContains('/teams/coaches');
        $this->assertResponseNotContains('/teams/edit/');
    }

    public function testViewOtherTeamIsForbidden(): void
    {
        $this->loginCoach();
        $this->get('/teams/view/3');

        $this->assertResponseCode(403);
    }

    public function testViewCoachedTeamListsAllActiveMembersToAdd(): void
    {
        $this->loginCoach();
        $this->get('/teams/view/2');

        $this->assertResponseOk();
        $this->assertResponseContains('Coach:</span>');
        // Member of another team: shown to be added, but without link to its page
        $this->assertResponseContains('First101 Last101');
        $this->assertResponseNotContains('/members/view/101');
        $this->assertResponseContains('/members/view/100');
        $this->assertResponseNotContains('/teams/edit/2');
    }

    public function testAddMemberOfOtherTeamToCoachedTeam(): void
    {
        $this->loginCoach();
        $this->get('/teams/add-member/2/101');

        $this->assertRedirect(['controller' => 'Teams', 'action' => 'view', 2]);
        $this->assertTrue($this->getTableLocator()->get('TeamsMembers')->exists(['team_id' => 2, 'member_id' => 101]));
    }

    public function testAddMemberToOtherTeamIsForbidden(): void
    {
        $this->loginCoach();
        $this->get('/teams/add-member/3/100');

        $this->assertResponseCode(403);
        $this->assertFalse($this->getTableLocator()->get('TeamsMembers')->exists(['team_id' => 3, 'member_id' => 100]));
    }

    public function testRemoveMemberFromOtherTeamIsForbidden(): void
    {
        $this->loginCoach();
        $this->get('/teams/remove-member/3/101');

        $this->assertResponseCode(403);
    }

    public function testTeamEditIsAdminOnly(): void
    {
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0], ['id' => 1]);
        $this->login();
        $this->get('/teams/edit/2');

        $this->assertResponseCode(403);
    }

    public function testTeamEditByAdmin(): void
    {
        $this->login();
        $this->get('/teams/edit/2');

        $this->assertResponseOk();
    }

    // ---------------------------------------------------------------- Coaches page

    public function testCoachesPageIsForbiddenToCoaches(): void
    {
        $this->loginCoach();
        $this->get('/teams/coaches');

        $this->assertResponseCode(403);
    }

    public function testCoachesPageAllowedToMemberEditAll(): void
    {
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0], ['id' => 1]);
        $this->addMember(103, [], true);
        $this->login();
        $this->get('/teams/coaches');

        $this->assertResponseOk();
        $this->assertResponseContains('Juniors');
        // Only the members having a user account can be chosen
        $this->assertResponseContains('First103 Last103');
        $this->assertResponseNotContains('First100 Last100');
    }

    public function testCoachesPageSavesCoaches(): void
    {
        $this->addMember(103, [], true);
        $this->login();
        $this->post('/teams/coaches', ['teams' => [
            2 => [TeamsCoach::COACH => [''], TeamsCoach::HELP_COACH => ['', '103']],
            3 => [TeamsCoach::COACH => ['', '1', '103'], TeamsCoach::HELP_COACH => ['']],
        ]]);

        $this->assertRedirect(['controller' => 'Teams', 'action' => 'coaches']);
        $rows = $this->getTableLocator()->get('TeamsCoaches')->find()
            ->orderBy(['team_id' => 'ASC', 'member_id' => 'ASC'])
            ->all()
            ->map(fn ($row) => [$row->team_id, $row->member_id, $row->type])
            ->toList();
        $this->assertSame([
            [2, 103, TeamsCoach::HELP_COACH],
            [3, 1, TeamsCoach::COACH],
            [3, 103, TeamsCoach::COACH],
        ], $rows);
    }

    public function testCoachesPageRefusesCoachAndHelpCoachOfSameTeam(): void
    {
        $this->login();
        $this->post('/teams/coaches', ['teams' => [
            2 => [TeamsCoach::COACH => ['1'], TeamsCoach::HELP_COACH => ['1']],
        ]]);

        $this->assertResponseOk();
        $this->assertFlashMessage('The coaches of Juniors could not be saved: a member cannot be coach and help coach of the same team.');
        // Unchanged
        $this->assertSame(1, $this->getTableLocator()->get('TeamsCoaches')->find()->count());
    }

    // ---------------------------------------------------------------- Meetings

    public function testMeetingOfOtherTeamIsForbidden(): void
    {
        $this->addMeeting(20, 3);
        $this->loginCoach();
        $this->get('/meetings/view/20');
        $this->assertResponseCode(403);

        $this->post('/presences/set-presence/20', ['member_id' => 101, 'state' => 1]);
        $this->assertResponseCode(403);

        $this->get('/meetings/edit/20');
        $this->assertResponseCode(403);
    }

    public function testMeetingOfCoachedTeam(): void
    {
        $this->addMeeting(20, 2);
        $this->loginCoach();
        $this->get('/meetings/view/20');

        $this->assertResponseOk();
        $this->assertResponseContains('First100');
        $this->assertResponseNotContains('First101');
    }

    public function testCreateMeetingForOtherTeamIsForbidden(): void
    {
        $this->loginCoach();
        $this->post('/meetings/add-multiple', ['team_id' => 3, 'name' => 'Training', 'mode' => 'once', 'date' => '2026-11-10', 'time' => '18:00']);

        $this->assertResponseCode(403);
        $this->assertSame(0, $this->getTableLocator()->get('Meetings')->find()->where(['team_id' => 3])->count());
    }

    public function testMoveMeetingToOtherTeamIsForbidden(): void
    {
        $this->addMeeting(20, 2);
        $this->loginCoach();
        $this->post('/meetings/edit/20', ['team_id' => 3]);

        $this->assertResponseCode(403);
        $this->assertSame(2, $this->getTableLocator()->get('Meetings')->get(20)->team_id);
    }

    // ---------------------------------------------------------------- Mailings

    public function testMailingsIndexOnlyShowsMailingsToCoachedTeams(): void
    {
        $this->addMailing([2], 'To juniors');
        $this->addMailing([2, 3], 'To both');
        $this->addMailing([3], 'To seniors');
        $this->loginCoach();
        $this->get('/mailings');

        $this->assertResponseOk();
        $this->assertResponseContains('To juniors');
        $this->assertResponseContains('To both');
        $this->assertResponseNotContains('To seniors');
    }

    public function testSendMailingToOtherTeamsIsForbidden(): void
    {
        $mailingId = $this->addMailing([2, 3], 'To both');
        $this->loginCoach();
        $this->get('/mailings/send/' . $mailingId);
        $this->assertResponseCode(403);

        $this->post('/mailings/send-next/' . $mailingId);
        $this->assertResponseCode(403);

        $this->post('/mailings/delete/' . $mailingId);
        $this->assertResponseCode(403);
        $this->assertSame(1, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testSendMailingToCoachedTeam(): void
    {
        $mailingId = $this->addMailing([2], 'To juniors');
        $this->loginCoach();
        $this->get('/mailings/send/' . $mailingId);

        $this->assertResponseOk();
        $this->assertResponseContains('m100@example.com');
    }

    public function testNewMailingOnlyOffersCoachedTeams(): void
    {
        $this->loginCoach();
        $this->get('/mailings/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Juniors');
        $this->assertResponseNotContains('Seniors');
    }

    public function testNewMailingIgnoresOtherTeams(): void
    {
        $this->loginCoach();
        $this->post('/mailings/add', ['title' => 'Hello', 'content' => 'Hi', 'team_ids' => [3]]);

        $this->assertResponseOk();
        $this->assertResponseContains('Select at least one team');
        $this->assertSame(0, $this->getTableLocator()->get('Mailings')->find()->count());
    }

    public function testCopyMailingKeepsOnlyCoachedTeams(): void
    {
        $mailingId = $this->addMailing([2, 3], 'To both');
        $this->loginCoach();
        $this->get('/mailings/add/' . $mailingId);

        $this->assertResponseOk();
        $this->assertResponseContains('value="2" checked');
        $this->assertResponseNotContains('value="3"');
    }

    public function testMailingsForMemberEditAllWithoutAdmin(): void
    {
        $this->addMailing([3], 'To seniors');
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0], ['id' => 1]);
        $this->login();
        $this->get('/mailings');

        $this->assertResponseOk();
        $this->assertResponseContains('To seniors');
    }
}
