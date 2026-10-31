<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Lists of members and the public pages of MembersController.
 * Member 1 coaches team 2 (members 100, 102), not team 3 (member 101).
 *
 * @uses \App\Controller\MembersController
 */
class MemberPagesTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.Fields', 'app.FieldTypes', 'app.Registrations',
    ];

    private const LISTS = ['list', 'list-email', 'pictures', 'index', 'invite'];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->addCoachedTeams();
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    public function testListsNeedMemberViewAll(): void
    {
        $this->loginWith(['MemberEditOwn', 'BillViewAll']);

        foreach (self::LISTS as $page) {
            $this->get('/members/' . $page);
            $this->assertResponseCode(403, $page);
        }
    }

    public function testListsOfAllMembers(): void
    {
        $this->loginWith(['MemberViewAll']);

        foreach (self::LISTS as $page) {
            $this->get('/members/' . $page . '/0/1');
            $this->assertResponseOk($page);
            $this->assertResponseContains('First101', $page);
        }
    }

    public function testListsOfCoachOnlyItsTeams(): void
    {
        $this->loginWith(['MemberViewAll', 'TeamScoped']);

        foreach (self::LISTS as $page) {
            $this->get('/members/' . $page . '/0/0');
            $this->assertResponseOk($page);
            $this->assertResponseContains('First100', $page);
            $this->assertResponseNotContains('First101', $page);
        }
    }

    public function testFamilyReductionOnlyForAdmins(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditAll']);
        $this->get('/members/family-reduction');
        $this->assertResponseCode(403);

        $this->loginWith(['Admin']);
        $this->get('/members/family-reduction');
        $this->assertResponseOk();
    }

    public function testNewMembersNotForCoaches(): void
    {
        $this->addMember(104, []);

        $this->loginWith(['MemberViewAll', 'TeamScoped']);
        $this->get('/members/new-members');
        $this->assertResponseCode(403);

        $this->loginWith(['MemberViewAll']);
        $this->get('/members/new-members');
        $this->assertResponseOk();
        $this->assertResponseContains('First104');
    }

    public function testRegisterFormIsPublic(): void
    {
        $this->get('/members/register');

        $this->assertResponseOk();
        $this->assertResponseContains('first_name');
    }

    public function testAddPhotoIdForms(): void
    {
        $this->get('/members/add-photo-id/100');
        $this->assertResponseCode(403);

        $this->cookie('member_hash', sha1('member100'));
        $this->get('/members/add-photo-id/100');
        $this->assertResponseOk();
        $this->get('/members/add-photo-id/101');
        $this->assertResponseCode(403);
    }
}
