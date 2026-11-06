<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Table\MembersTable;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * City (site) stored on the member: one city per member, its teams are all in this city
 * Site 1 (team 1 and 2), site 2 "Lausanne" (team 3). Member 100 is in teams 1 and 2, member 101 in no team.
 *
 * @uses \App\Model\Table\MembersTable
 * @uses \App\Model\Table\TeamsTable
 * @uses \App\Controller\MembersController
 * @uses \App\Controller\TeamsController
 */
class MemberSiteTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Meetings', 'app.Presences', 'app.Bills', 'app.Fields', 'app.FieldTypes',
        'app.Registrations', 'app.MemberDocs', 'app.ShopItems', 'app.MemberOrders',
    ];

    private MembersTable $Members;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->addSite(2, 'Lausanne');
        $this->addTeam(2, 'Juniors');
        $this->addTeam(3, 'Lausanne team', true, 2);
        $this->addMember(100, [1, 2]);
        $this->addMember(101);
        $this->Members = $this->getTableLocator()->get('Members');
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    /**
     * @return int[] team ids of the member
     */
    private function teamIds(int $memberId): array
    {
        $ids = array_map(fn($team) => $team->id, $this->Members->get($memberId, contain: ['Teams'])->teams);
        sort($ids);

        return $ids;
    }

    // ---------------------------------------------------------------- model

    public function testNewMemberWithoutCityGetsTheDefaultOne(): void
    {
        $form = $this->memberForm();
        unset($form['site_id']);
        $member = $this->Members->newEntity($form, ['fields' => MembersTable::STAFF_FIELDS]);
        $member->hash = MembersTable::newHash();

        $this->assertNotFalse($this->Members->save($member));
        $this->assertSame(MembersTable::DEFAULT_SITE_ID, $this->Members->get($member->id)->site_id);
        // Column default (members inserted without city, as by the migration)
        $this->assertSame(1, $this->Members->get(101)->site_id);
    }

    public function testUnknownCityIsRefused(): void
    {
        $member = $this->Members->get(101);
        $this->Members->patchEntity($member, ['site_id' => 9]);

        $this->assertFalse($this->Members->save($member));
        $this->assertArrayHasKey('site_id', $member->getErrors());
    }

    public function testChangingCityLeavesTheTeamsOfTheOtherCity(): void
    {
        $member = $this->Members->get(100, contain: ['Teams']);
        $this->Members->patchEntity($member, ['site_id' => 2], ['fields' => MembersTable::OWN_FIELDS]);

        $this->assertNotFalse($this->Members->save($member));
        $this->assertSame(2, $this->Members->get(100)->site_id);
        $this->assertSame([], $this->teamIds(100));
    }

    public function testChangingCityWithoutTheTeamsLoaded(): void
    {
        $member = $this->Members->get(100);
        $member->site_id = 2;

        $this->assertNotFalse($this->Members->save($member));
        $this->assertSame([], $this->teamIds(100));
    }

    public function testChangingCityKeepsTheTeamsChosenInTheNewCity(): void
    {
        $member = $this->Members->get(100, contain: ['Teams']);
        $this->Members->patchEntity($member, ['site_id' => 2, 'teams' => ['_ids' => [1, 3]]], ['fields' => MembersTable::STAFF_FIELDS]);

        $this->assertNotFalse($this->Members->save($member));
        $this->assertSame([3], $this->teamIds(100));
    }

    public function testSameCityKeepsTheTeams(): void
    {
        $member = $this->Members->get(100, contain: ['Teams']);
        $this->Members->patchEntity($member, ['site_id' => '1', 'first_name' => 'Changed'], ['fields' => MembersTable::OWN_FIELDS]);

        $this->assertNotFalse($this->Members->save($member));
        $this->assertSame([1, 2], $this->teamIds(100));
    }

    public function testTeamOfAnotherCityIsRefused(): void
    {
        $member = $this->Members->get(100, contain: ['Teams']);
        $this->Members->patchEntity($member, ['teams' => ['_ids' => [1, 3]]], ['fields' => MembersTable::STAFF_FIELDS]);

        $this->assertFalse($this->Members->save($member));
        $this->assertArrayHasKey('teamsInSite', $member->getError('teams'));
        $this->assertSame([1, 2], $this->teamIds(100));
    }

    public function testFinderFiltersOnTheCityOfTheMember(): void
    {
        $ids = fn(int $siteId, int $memberFilter = 1) => $this->Members->find('Members', teamId: 0, memberFilter: $memberFilter, siteId: $siteId)
            ->all()->extract('id')->toList();
        $this->Members->updateAll(['site_id' => 2], ['id' => 101]);

        // Members without team are in their city too
        $this->assertEqualsCanonicalizing([1, 100], $ids(1));
        $this->assertSame([101], $ids(2));
        $this->assertSame([101], $ids(2, 5));
        $this->assertSame([], $ids(1, 5));
        $this->assertSame(1, $this->Members->CountActiveMembers(2));
        $this->assertSame(1, $this->Members->CountActiveWithoutTeam(2));
        $this->assertSame(0, $this->Members->CountActiveWithoutTeam(1));
    }

    public function testTeamWithMembersCannotMoveToAnotherCity(): void
    {
        $teams = $this->getTableLocator()->get('Teams');
        $team = $teams->patchEntity($teams->get(2), ['site_id' => 2]);

        $this->assertFalse($teams->save($team));
        $this->assertArrayHasKey('site_id', $team->getErrors());

        // Without members it can
        $empty = $teams->patchEntity($teams->get(3), ['site_id' => 1]);
        $this->assertNotFalse($teams->save($empty));
    }

    // ---------------------------------------------------------------- registration and forms

    public function testRegistrationMustChooseACity(): void
    {
        $form = $this->memberForm(['first_name' => 'Nocity']);
        unset($form['site_id']);
        // Texts are checked in English
        $this->session(['lng' => 'en']);

        $this->post('/members/register', $form);

        $this->assertResponseOk();
        $this->assertFalse($this->Members->exists(['first_name' => 'Nocity']));
        $this->assertResponseContains('Choose a location');
    }

    public function testRegistrationSavesTheChosenCity(): void
    {
        $this->post('/members/register', $this->memberForm(['first_name' => 'Newcity', 'site_id' => 2]));

        $this->assertRedirect(['controller' => 'Pages', 'action' => 'Home']);
        $this->assertSame(2, $this->Members->find()->where(['first_name' => 'Newcity'])->firstOrFail()->site_id);
        // Captured by EmailTrait, never sent
        $this->assertMailSentTo('new@example.com');
    }

    public function testRegisterFormListsTheCities(): void
    {
        $this->get('/members/register');

        $this->assertResponseOk();
        $this->assertResponseContains('name="site_id"');
        $this->assertResponseContains('Lausanne');
    }

    public function testMemberChangesItsCityByLink(): void
    {
        $this->cookie('member_hash', sha1('member100'));

        $this->post('/members/edit/100', ['first_name' => 'Moved', 'site_id' => 2]);

        $this->assertRedirect(['controller' => 'Members', 'action' => 'myPage']);
        $this->assertSame(2, $this->Members->get(100)->site_id);
        $this->assertSame([], $this->teamIds(100));
    }

    public function testEditFormOnlyListsTheTeamsOfTheCity(): void
    {
        $this->loginWith();

        $this->get('/members/edit/100');

        $this->assertResponseOk();
        $this->assertEqualsCanonicalizing([1, 2], array_keys($this->viewVariable('teams')));
        $this->assertResponseNotContains('Lausanne team');
        $this->assertResponseContains('edit the member again to choose the teams of the new location.');

        $this->Members->updateAll(['site_id' => 2], ['id' => 101]);
        $this->get('/members/edit/101');
        $this->assertSame([3], array_keys($this->viewVariable('teams')));
    }

    /**
     * The staff changes the city: the teams (of the old city, the only ones in the form) are removed
     */
    public function testEditChangingTheCityRemovesTheTeams(): void
    {
        $this->loginWith();

        $this->post('/members/edit/100', $this->memberForm(['site_id' => 2, 'teams' => ['_ids' => [1, 2]]]));

        $this->assertRedirect(['controller' => 'Members', 'action' => 'view', 100]);
        $this->assertSame(2, $this->Members->get(100)->site_id);
        $this->assertSame([], $this->teamIds(100));

        // Second edit: the teams of the new city
        $this->get('/members/edit/100');
        $this->assertSame([3], array_keys($this->viewVariable('teams')));
        $this->post('/members/edit/100', $this->memberForm(['site_id' => 2, 'teams' => ['_ids' => [3]]]));
        $this->assertSame([3], $this->teamIds(100));
    }

    public function testAddFormPreselectsTheCityOfThePages(): void
    {
        $this->Members->updateAll(['pref_site_id' => 2], ['id' => 1]);
        $this->loginWith(['Admin', 'MemberViewAll', 'MemberEditAll']);

        $this->get('/members/add');

        $this->assertResponseOk();
        $this->assertResponseContains('<option value="2" selected="selected">Lausanne</option>');
        $this->assertResponseContains('team-select');
        // Only the teams of the preselected city
        $this->assertSame([3], array_keys($this->viewVariable('teams')));
    }

    public function testAddMemberInAnotherCityThanItsTeams(): void
    {
        $this->loginWith(['Admin', 'MemberViewAll', 'MemberEditAll']);

        $this->post('/members/add', $this->memberForm(['first_name' => 'Moved', 'site_id' => 1, 'teams' => ['_ids' => [2, 3]]]));

        // Saved in the chosen city, without the team of the other city
        $member = $this->Members->find()->where(['first_name' => 'Moved'])->contain(['Teams'])->firstOrFail();
        $this->assertSame(1, $member->site_id);
        $this->assertSame([2], array_map(fn($team) => $team->id, $member->teams));
    }

    // ---------------------------------------------------------------- teams page

    public function testTeamPageOnlyOffersTheMembersOfItsCity(): void
    {
        $this->Members->updateAll(['site_id' => 2], ['id' => 101]);
        $this->loginWith();

        $this->get('/teams/view/3');
        $offered = array_map(fn($member) => $member->id, $this->viewVariable('membersOut'));
        $this->assertSame([101], $offered);

        $this->get('/teams/view/2');
        $offered = array_map(fn($member) => $member->id, $this->viewVariable('membersOut'));
        $this->assertNotContains(101, $offered);
    }

    public function testAddMemberOfAnotherCityToATeamIsRefused(): void
    {
        $this->loginWith();

        $this->post('/teams/add-member/3/101');

        $this->assertRedirect(['controller' => 'Teams', 'action' => 'view', 3]);
        $this->assertFlashElement('flash/error');
        $this->assertSame([], $this->teamIds(101));

        $this->post('/teams/add-member/2/101');
        $this->assertSame([2], $this->teamIds(101));
    }

    // ---------------------------------------------------------------- city menu

    public function testCityMenuOnlyForAdmins(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditAll', 'BillViewAll']);
        $this->get('/members');
        $this->assertResponseOk();
        $this->assertResponseNotContains('/members/set-site/2');

        $this->loginWith(['Admin', 'MemberViewAll']);
        $this->get('/members');
        $this->assertResponseContains('/members/set-site/2');
    }

    public function testStaffListsTheMembersOfTheirCity(): void
    {
        $this->Members->updateAll(['site_id' => 2], ['id' => 101]);
        // The menu choice of a not admin is ignored
        $this->Members->updateAll(['pref_site_id' => 2], ['id' => 1]);
        $this->loginWith(['MemberViewAll']);

        $this->get('/members');

        $this->assertResponseOk();
        $listed = array_map(fn($member) => $member->id, $this->viewVariable('members')->toArray());
        $this->assertEqualsCanonicalizing([1, 100], $listed);
    }
}
