<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Members without account open their page with a link (hash), kept in the member_hash cookie.
 * The link only gives access to its own member: here member 100, member 1 is another one.
 *
 * @uses \App\Controller\MembersController
 * @uses \App\Controller\Component\MemberManagementComponent
 */
class MemberHashAccessTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Meetings', 'app.Presences', 'app.Bills', 'app.Fields', 'app.FieldTypes',
        'app.Registrations', 'app.MemberDocs', 'app.ShopItems', 'app.MemberOrders',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->addMember(100, [1], ['checked' => 0, 'registered' => 1]);
        $this->getTableLocator()->get('Members')->updateAll(['checked' => 0], ['id' => 1]);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function asMember100(): void
    {
        $this->cookie('member_hash', sha1('member100'));
    }

    private function member(int $id)
    {
        return $this->getTableLocator()->get('Members')->get($id);
    }

    public function testEditOwnMember(): void
    {
        $this->asMember100();

        $this->get('/members/edit/100');
        $this->assertResponseOk();

        $this->post('/members/edit/100', ['first_name' => 'Changed']);
        $this->assertRedirect(['controller' => 'Members', 'action' => 'my-page']);
        $this->assertSame('Changed', $this->member(100)->first_name);
    }

    public function testEditOtherMemberRefused(): void
    {
        $this->asMember100();

        $this->get('/members/edit/1');
        $this->assertResponseCode(403);

        $this->post('/members/edit/1', ['first_name' => 'Hacked']);
        $this->assertResponseCode(403);
        $this->assertNotSame('Hacked', $this->member(1)->first_name);
    }

    public function testCheckedOwnMember(): void
    {
        $this->asMember100();

        $this->post('/members/checked/100');

        $this->assertRedirect(['controller' => 'Members', 'action' => 'myPage']);
        $this->assertTrue($this->member(100)->checked);
    }

    public function testCheckedOtherMemberRefused(): void
    {
        $this->asMember100();

        $this->post('/members/checked/1');

        $this->assertResponseCode(403);
        $this->assertFalse($this->member(1)->checked);
    }

    public function testActiveOtherMemberRefused(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['active' => false], ['id' => 1]);
        $this->asMember100();

        $this->post('/members/active/1');

        $this->assertResponseCode(403);
        $this->assertFalse($this->member(1)->active);
    }

    public function testCancelOwnRegistration(): void
    {
        $this->asMember100();

        $this->post('/members/cancel-registration/100', ['comment' => 'Moving']);

        $this->assertRedirect(['controller' => 'Members', 'action' => 'myPage']);
        $this->assertFalse($this->member(100)->active);
    }

    public function testCancelOtherRegistrationRefused(): void
    {
        $this->asMember100();

        $this->post('/members/cancel-registration/1', ['comment' => 'Moving']);

        $this->assertResponseCode(403);
        $this->assertTrue($this->member(1)->active);
    }

    /**
     * A logged user keeping an old member link cookie is checked with its own rights
     */
    public function testLoggedUserWithOldCookieUsesItsRights(): void
    {
        $this->asMember100();
        $this->loginWith(['MemberEditOwn']);

        $this->post('/members/checked/100');

        $this->assertResponseCode(403);
        $this->assertFalse($this->member(100)->checked);
    }

    public function testWithoutLinkNorLogin(): void
    {
        $this->post('/members/checked/100');
        $this->assertResponseCode(403);

        $this->get('/members/edit/100');
        $this->assertRedirectContains('/users/login');
        $this->assertFalse($this->member(100)->checked);
    }
}
