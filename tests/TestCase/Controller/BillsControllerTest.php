<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Bills of site 1: 10 of member 1 (logged), 11 of member 100
 *
 * @uses \App\Controller\BillsController
 */
class BillsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.BillTemplates',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->addMember(100, [1]);
        $this->addBill(10, 1, ['label' => 'Own fee']);
        $this->addBill(11, 100, ['label' => 'Other fee']);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function bill(int $id)
    {
        return $this->getTableLocator()->get('Bills')->get($id);
    }

    /**
     * Data of the invoice form
     */
    private function form(array $values = []): array
    {
        return $values + [
            'label' => 'Shirt', 'amount' => 30, 'due_date' => '2026-12-31', 'site_id' => 1, 'printed' => 0, 'paid' => 0,
            'reminder' => 0, 'canceled' => 0, 'link_membership_fee' => 0,
        ];
    }

    public function testIndexRequiresLogin(): void
    {
        $this->get('/bills');

        $this->assertRedirectContains('/users/login');
    }

    public function testIndex(): void
    {
        $this->loginWith(['BillViewAll']);

        $this->get('/bills/index/0/0');

        $this->assertResponseOk();
        $this->assertResponseContains('Own fee');
        $this->assertResponseContains('Other fee');
    }

    public function testIndexForbiddenWithoutBillViewAll(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditAll']);

        $this->get('/bills');

        $this->assertResponseCode(403);
    }

    public function testViewOwnBillOnly(): void
    {
        $this->loginWith(['MemberEditOwn']);

        $this->get('/bills/view/10');
        $this->assertResponseOk();
        $this->assertResponseContains('Own fee');

        $this->get('/bills/view/11');
        $this->assertResponseCode(403);
    }

    public function testAddForTheMemberOfThePage(): void
    {
        $this->loginWith(['BillViewAll', 'BillEditAll']);

        $this->get('/bills/add/100');
        $this->assertResponseOk();
        // The member of the form can not be changed
        // As the add form: an empty token, set by the controller
        $this->post('/bills/add/100', $this->form(['tokenhash' => '', 'member_id' => 1, 'member' => ['id' => 100, 'discount' => 50]]));

        $bill = $this->getTableLocator()->get('Bills')->find()->where(['label' => 'Shirt'])->firstOrFail();
        $this->assertRedirect(['controller' => 'Bills', 'action' => 'pdf', $bill->id]);
        $this->assertSame(100, $bill->member_id);
        $this->assertSame(30, $bill->amount);
        $this->assertNotEmpty($bill->tokenhash);
        $this->assertSame(0, $this->getTableLocator()->get('Members')->get(100)->discount);
    }

    public function testAddWithoutMember(): void
    {
        $this->loginWith(['BillViewAll', 'BillEditAll']);

        $this->get('/bills/add');

        $this->assertRedirect(['controller' => 'Bills', 'action' => 'index']);
    }

    public function testAddForbiddenWithoutBillEditAll(): void
    {
        $this->loginWith(['BillViewAll']);

        $this->post('/bills/add/100', $this->form());

        $this->assertResponseCode(403);
        $this->assertSame(2, $this->getTableLocator()->get('Bills')->find()->count());
    }

    public function testEdit(): void
    {
        $this->loginWith(['BillViewAll', 'BillEditAll']);
        $this->getTableLocator()->get('Bills')->updateAll(['printed' => true], ['id' => 11]);

        $this->get('/bills/edit/11');
        $this->assertResponseOk();
        $this->post('/bills/edit/11', $this->form(['amount' => 45, 'member_id' => 1]));

        $this->assertRedirect(['controller' => 'Bills', 'action' => 'pdf', 11]);
        $bill = $this->bill(11);
        $this->assertSame(45, $bill->amount);
        $this->assertSame(100, $bill->member_id);
        // Printed again after a change
        $this->assertFalse($bill->printed);
    }

    public function testPaidBillOnlyEditedByAdmin(): void
    {
        $this->getTableLocator()->get('Bills')->updateAll(['paid' => true], ['id' => 11]);

        $this->loginWith(['BillViewAll', 'BillEditAll']);
        $this->post('/bills/edit/11', $this->form(['amount' => 45]));
        $this->assertFlashElement('flash/error');
        $this->assertSame(10, $this->bill(11)->amount);

        $this->loginWith(['BillViewAll', 'BillEditAll', 'Admin']);
        $this->post('/bills/edit/11', $this->form(['amount' => 45, 'paid' => 1]));
        $this->assertSame(45, $this->bill(11)->amount);
    }

    public function testEditForbiddenWithoutBillEditAll(): void
    {
        $this->loginWith(['BillViewAll']);

        $this->post('/bills/edit/11', $this->form(['amount' => 45]));

        $this->assertResponseCode(403);
        $this->assertSame(10, $this->bill(11)->amount);
    }

    /**
     * The membership buttons fill the fee of the teams, limited to the maximum of the site
     */
    public function testAddFormMembershipFee(): void
    {
        $this->getTableLocator()->get('Teams')->updateAll(['membership_fee' => 300], ['id' => 1]);
        $this->getTableLocator()->get('Sites')->updateAll(['feeMax' => 250], ['id' => 1]);
        $this->loginWith(['BillViewAll', 'BillEditAll']);

        $this->get('/bills/add/100');

        $this->assertResponseContains('$("#amount").val("250");');
        $this->assertResponseContains('$("#amount").val("125");');
    }
}
