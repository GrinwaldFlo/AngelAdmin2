<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Accounts: login redirect, own account, registration from the member link and password reset.
 * Role 1 is the admin role of the fixture, role 4 the role of the members (MemberEditOwn only).
 *
 * @uses \App\Controller\UsersController
 * @uses \App\Model\Table\UsersTable
 */
class UserAccountSecurityTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.Meetings', 'app.Presences',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->getTableLocator()->get('Roles')->insertQuery()
            ->insert(['id', 'name', 'MemberViewAll', 'MemberEditAll', 'MemberEditOwn', 'BillViewAll', 'BillEditAll', 'Admin'])
            ->values(['id' => 4, 'name' => 'Member', 'MemberViewAll' => 0, 'MemberEditAll' => 0, 'MemberEditOwn' => 1, 'BillViewAll' => 0, 'BillEditAll' => 0, 'Admin' => 0])
            ->execute();
        $this->getTableLocator()->get('Configurations')->insertQuery()->insert(['label', 'value'])
            ->values(['label' => 'email', 'value' => 'club@example.com'])
            ->execute();
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en', 'email' => 'm1@example.com'], []);
        $this->setPassword(1, 'good password');
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function setPassword(int $userId, string $password): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get($userId);
        $user->password = $password;
        $users->saveOrFail($user);
    }

    private function user(int $id)
    {
        return $this->getTableLocator()->get('Users')->get($id);
    }

    private function loginAsMember(): void
    {
        $this->getTableLocator()->get('Users')->updateAll(['role_id' => 4], ['id' => 1]);
        $this->session(['Auth' => $this->user(1)]);
    }

    // ---------------------------------------------------------------- login

    public function testLoginRedirectsToALocalPage(): void
    {
        $this->post('/users/login?redirect=%2Fmembers%2Fview%2F1', ['username' => 'Lorem ipsum dolor sit amet', 'password' => 'good password']);

        $this->assertRedirect('/members/view/1');
    }

    public function testLoginNeverRedirectsToAnotherSite(): void
    {
        foreach (['https://evil.example/login', '//evil.example', 'javascript:alert(1)'] as $target) {
            $this->post('/users/login?redirect=' . urlencode($target), ['username' => 'Lorem ipsum dolor sit amet', 'password' => 'good password']);

            $this->assertRedirect('/', $target);
        }
    }

    public function testWrongPassword(): void
    {
        $this->post('/users/login', ['username' => 'Lorem ipsum dolor sit amet', 'password' => 'wrong']);

        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
        $this->assertSessionNotHasKey('Auth');
    }

    // ---------------------------------------------------------------- own account

    public function testMemberCannotChangeItsRole(): void
    {
        $this->loginAsMember();

        $this->post('/users/edit/1', ['username' => 'new name', 'role_id' => 1, 'member_id' => 2]);

        $user = $this->user(1);
        $this->assertSame(4, $user->role_id);
        $this->assertSame(1, $user->member_id);
        $this->assertSame('new name', $user->username);
        $this->assertRedirect(['controller' => 'Users', 'action' => 'view', 1]);
    }

    public function testMemberCannotEditAnotherAccount(): void
    {
        $this->addMember(100);
        $this->addUser(100, 100, 4);
        $this->loginAsMember();

        $this->post('/users/edit/100', ['username' => 'taken']);

        $this->assertResponseCode(403);
        $this->assertSame('user100', $this->user(100)->username);
    }

    public function testAdminChangesTheRole(): void
    {
        $this->addMember(100);
        $this->addUser(100, 100, 4);
        $this->session(['Auth' => $this->user(1)]);

        $this->post('/users/edit/100', ['role_id' => 1]);

        $this->assertSame(1, $this->user(100)->role_id);
        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);
    }

    // ---------------------------------------------------------------- registration from the member link

    public function testRegisterAlwaysGivesTheMemberRole(): void
    {
        $this->addMember(100);

        $this->post('/users/register/' . sha1('member100'), [
            'username' => 'ana', 'password' => 'long enough', 'passwordConfirmation' => 'long enough', 'role_id' => 1, 'member_id' => 1, 'tokenhash' => 'x',
        ]);

        $this->assertRedirect('/');
        $user = $this->getTableLocator()->get('Users')->find()->where(['username' => 'ana'])->firstOrFail();
        $this->assertSame(4, $user->role_id);
        $this->assertSame(100, $user->member_id);
        $this->assertSame('', $user->tokenhash);
    }

    public function testRegisterNeedsAPassword(): void
    {
        $this->addMember(100);
        $users = $this->getTableLocator()->get('Users');

        $this->post('/users/register/' . sha1('member100'), ['username' => 'ana', 'password' => 'short', 'passwordConfirmation' => 'short']);
        $this->post('/users/register/' . sha1('member100'), ['username' => 'ana', 'password' => 'long enough', 'passwordConfirmation' => 'other one']);
        $this->post('/users/register/' . sha1('member100'), ['username' => 'ana']);

        $this->assertFalse($users->exists(['username' => 'ana']));
    }

    public function testRegisterOnlyOneAccountPerMember(): void
    {
        $this->post('/users/register/' . str_repeat('1', 40), ['username' => 'second', 'password' => 'long enough', 'passwordConfirmation' => 'long enough']);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
        $this->assertFalse($this->getTableLocator()->get('Users')->exists(['username' => 'second']));
    }

    // ---------------------------------------------------------------- password reset

    public function testForgotPasswordCreatesARandomToken(): void
    {
        $this->post('/users/forgot-password', ['email_or_username' => 'Lorem ipsum dolor sit amet']);
        $first = $this->user(1)->tokenhash;
        $this->post('/users/forgot-password', ['email_or_username' => 'm1@example.com']);
        $second = $this->user(1)->tokenhash;

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $first);
        $this->assertNotSame($first, $second);
        $this->assertMailSentTo('m1@example.com');
        $this->assertMailContains('/users/reset/' . $second);
    }

    public function testForgotPasswordOfUnknownUserSendsNothing(): void
    {
        $this->post('/users/forgot-password', ['email_or_username' => 'nobody']);

        $this->assertFlashElement('flash/success');
        $this->assertNoMailSent();
    }

    public function testResetChangesOnlyThePasswordOnce(): void
    {
        $token = str_repeat('a', 40);
        $this->getTableLocator()->get('Users')->updateAll(['tokenhash' => $token, 'role_id' => 4], ['id' => 1]);

        $this->post('/users/reset/' . $token, ['password' => 'new password', 'role_id' => 1, 'username' => 'other']);

        $this->assertRedirect('/');
        $user = $this->user(1);
        $this->assertSame(4, $user->role_id);
        $this->assertSame('Lorem ipsum dolor sit amet', $user->username);
        $this->assertSame('', $user->tokenhash);
        // The link does not work a second time
        $this->get('/users/reset/' . $token);
        $this->assertResponseCode(404);
    }

    public function testResetNeedsAPassword(): void
    {
        $token = str_repeat('a', 40);
        $this->getTableLocator()->get('Users')->updateAll(['tokenhash' => $token], ['id' => 1]);

        $this->post('/users/reset/' . $token, ['password' => '']);

        $this->assertResponseOk();
        $this->assertSame($token, $this->user(1)->tokenhash);
        // The old password still works
        $this->post('/users/login', ['username' => 'Lorem ipsum dolor sit amet', 'password' => 'good password']);
        $this->assertRedirect('/');
    }

    public function testResetWithoutToken(): void
    {
        $this->get('/users/reset');

        $this->assertRedirect('/');
        $this->assertFlashElement('flash/error');
    }

    public function testLogout(): void
    {
        $this->session(['Auth' => $this->user(1)]);

        $this->get('/users/logout');

        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
        $this->assertSessionNotHasKey('Auth');
    }

    public function testEmailLoginSendsTheLinkOfTheMember(): void
    {
        $this->post('/users/email-login', ['full_name' => ' lorem ipsum dolor sit amet Lorem ipsum dolor sit amet ']);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
        $this->assertMailSentTo('m1@example.com');
        $this->assertMailContains('/members/my-page/');
    }

    public function testEmailLoginOfUnknownNameOnlyWarnsTheClub(): void
    {
        $this->post('/users/email-login', ['full_name' => 'Nobody <b>Here</b>']);

        // Same message: nobody learns if the member exists
        $this->assertFlashElement('flash/success');
        $this->assertMailCount(1);
        $this->assertMailSentTo('club@example.com');
    }

    public function testEmailLoginNeedsAPost(): void
    {
        $this->get('/users/email-login');

        $this->assertResponseCode(405);
    }
}
