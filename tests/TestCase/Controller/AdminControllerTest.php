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

    public function testGenerateMembershipAllSitesListsNobody(): void
    {
        $this->selectSecondSite();
        $this->getTableLocator()->get('Members')->updateAll(['pref_site_id' => 0, 'language' => 'en'], ['id' => 1]);

        $this->loginAdmin();
        $this->enableRetainFlashMessages();
        $this->get('/admin/generate-membership');

        $this->assertResponseOk();
        $this->assertCount(0, $this->viewVariable('members'));
        $this->assertNull($this->viewVariable('site'));
        $this->assertFlashMessage('Select a site to create membership fees');
    }

    public function testGenerateMembershipAllSitesCreatesNoBill(): void
    {
        $this->selectSecondSite();
        $this->getTableLocator()->get('Members')->updateAll(['pref_site_id' => 0], ['id' => 1]);
        $bills = $this->getTableLocator()->get('Bills');
        $before = $bills->find()->count();

        $this->loginAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/generate-membership', ['MemberId' => [1 => 1]]);

        $this->assertRedirectContains('/admin/generate-membership');
        $this->assertSame($before, $bills->find()->count());
    }

    public function testGenerateMembershipFeeMaxFromSite(): void
    {
        $this->getTableLocator()->get('Sites')->updateAll(['feeMax' => 300], ['id' => 1]);
        $this->getTableLocator()->get('Teams')->updateAll(['membership_fee' => 500], ['id' => 1]);
        $this->getTableLocator()->get('Members')->updateAll(['multi_payment' => 1, 'discount' => 0], ['id' => 1]);

        $this->loginAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/generate-membership', ['MemberId' => [1 => 1]]);

        $this->assertRedirectContains('/bills/pdf/-1');
        $bill = $this->getTableLocator()->get('Bills')->find()
            ->where(['member_id' => 1, 'link_membership_fee' => 1])
            ->orderByDesc('id')
            ->firstOrFail();
        $this->assertSame(1, $bill->site_id);
        $this->assertEquals(300, $bill->amount);
    }

    public function testResetMembershipNeedsTheCodeOfThePage(): void
    {
        $this->enableCsrfToken();
        $members = $this->getTableLocator()->get('Members');
        $members->updateAll(['membership_fee_paid' => 1], ['id' => 1]);
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1), 'resetValidation' => 1234]);

        $this->get('/admin/reset-membership');
        $this->assertResponseOk();
        $this->assertSame(1, $members->get(1)->membership_fee_paid);

        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1), 'resetValidation' => 1234]);
        $this->post('/admin/reset-membership/999');
        $this->assertSame(1, $members->get(1)->membership_fee_paid);

        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1), 'resetValidation' => 1234]);
        $this->post('/admin/reset-membership/1234');
        $this->assertSame(0, $members->get(1)->membership_fee_paid);
    }

    /**
     * A link of another site must not reset the members (GET request)
     */
    public function testResetNeedsAPost(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['registered' => 1], ['id' => 1]);
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1), 'resetValidation' => 1234]);

        $this->get('/admin/reset-registration/1234');

        $this->assertResponseCode(405);
        $this->assertTrue($this->getTableLocator()->get('Members')->get(1)->registered);
    }

    public function testResetForbiddenToNonAdmin(): void
    {
        $this->enableCsrfToken();
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0], ['id' => 1]);
        $this->getTableLocator()->get('Members')->updateAll(['registered' => 1], ['id' => 1]);
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1), 'resetValidation' => 1234]);

        $this->post('/admin/reset-registration/1234');

        $this->assertRedirect('/');
        $this->assertTrue($this->getTableLocator()->get('Members')->get(1)->registered);
    }

    public function testViewLog(): void
    {
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)]);

        $this->get('/admin/view-log/error');
        $this->assertResponseOk();

        $this->get('/admin/view-log/..%2F..%2Fconfig%2Fapp');
        $this->assertResponseCode(404);
    }
}
