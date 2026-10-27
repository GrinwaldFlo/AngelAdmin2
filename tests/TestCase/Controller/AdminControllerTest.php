<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AdminController Test Case
 *
 * @uses \App\Controller\AdminController
 */
class AdminControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Members',
        'app.Bills',
        'app.Presences',
        'app.Meetings',
        'app.Users',
        'app.Roles',
        'app.Teams',
        'app.TeamsMembers',
        'app.Sites',
        'app.Registrations',
        'app.Configurations',
        'app.Contents',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
    }

    private function loginAdmin(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)]);
    }

    public function testDashboardRequiresLogin(): void
    {
        $this->get('/admin/dashboard');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');
    }

    public function testDashboardRedirectsNonAdmin(): void
    {
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0], ['id' => 1]);
        $this->loginAdmin();
        $this->get('/admin/dashboard');

        $this->assertRedirect('/');
        $this->assertNull($this->viewVariable('members'));
        $this->assertResponseNotContains('dash-kpis');
    }

    public function testDashboardAllSites(): void
    {
        $this->loginAdmin();
        $this->get('/admin/dashboard');

        $this->assertResponseOk();
        $this->assertResponseContains('dash-kpis');
        $this->assertSame(1, $this->viewVariable('members')['active']);
        $this->assertSame(0, $this->viewVariable('members')['withoutTeam']);
        $this->assertCount(1, $this->viewVariable('teams'));
    }

    private function selectSecondSite(): void
    {
        $this->getTableLocator()->get('Sites')->insertQuery()
            ->insert(['id', 'city', 'feeMax', 'sender_email', 'sender'])
            ->values(['id' => 2, 'city' => 'Second city', 'feeMax' => 1, 'sender_email' => 'second@example.com', 'sender' => 'Second'])
            ->execute();
        $this->getTableLocator()->get('Members')->updateAll(['pref_site_id' => 2], ['id' => 1]);
    }

    public function testDashboardOtherSite(): void
    {
        $this->selectSecondSite();

        $this->loginAdmin();
        $this->get('/admin/dashboard');

        $this->assertResponseOk();
        $this->assertResponseContains('Second city');
        $this->assertSame(0, $this->viewVariable('members')['active']);
        $this->assertNull($this->viewVariable('members')['withoutTeam']);
        $this->assertCount(0, $this->viewVariable('teams'));
    }

    public function testGenerateMembershipSite(): void
    {
        $this->loginAdmin();
        $this->get('/admin/generate-membership');

        $this->assertResponseOk();
        $this->assertCount(1, $this->viewVariable('members'));
        $this->assertCount(1, $this->viewVariable('teams'));
    }

    public function testGenerateMembershipOtherSite(): void
    {
        $this->selectSecondSite();

        $this->loginAdmin();
        $this->get('/admin/generate-membership');

        $this->assertResponseOk();
        $this->assertResponseContains('Second city');
        $this->assertCount(0, $this->viewVariable('members'));
        $this->assertCount(0, $this->viewVariable('teams'));
    }

    public function testGenerateMembershipBillSite(): void
    {
        $this->selectSecondSite();
        $this->getTableLocator()->get('Members')->updateAll(['multi_payment' => 1], ['id' => 1]);

        $this->loginAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/generate-membership', ['MemberId' => [1 => 1]]);

        $this->assertRedirectContains('/bills/pdf/-1');
        $bill = $this->getTableLocator()->get('Bills')->find()
            ->where(['member_id' => 1, 'link_membership_fee' => 1])
            ->orderByDesc('id')
            ->firstOrFail();
        $this->assertSame(2, $bill->site_id);
    }
}
