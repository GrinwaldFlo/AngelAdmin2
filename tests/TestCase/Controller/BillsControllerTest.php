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

    /**
     * Creation date of a bill, years before today
     */
    private function yearsAgo(int $years): string
    {
        return date('Y-m-d H:i:s', strtotime("-$years years"));
    }

    /**
     * Bills 10 and 11 of setUp created today, member 101 without bill
     * The configured year is ahead (season being prepared): the seasons are counted from today
     */
    private function prepareSearch(): void
    {
        $this->getTableLocator()->get('Configurations')->insertQuery()
            ->insert(['label', 'value'])
            ->values(['label' => 'year', 'value' => (string)((int)date('Y') + 9)])
            ->values(['label' => 'firstDaySeason', 'value' => '1.7'])
            ->execute();
        $bills = $this->getTableLocator()->get('Bills');
        $bills->updateAll(['created' => $this->yearsAgo(0)], ['id IN' => [10, 11]]);
        $bills->updateAll(['label' => 'T-Shirt Angels 2026 - S'], ['id' => 11]);
        $this->addMember(101, [1]);
        $this->loginWith(['BillViewAll']);
    }

    public function testSearchListsBillsAndMembersWithoutBill(): void
    {
        $this->prepareSearch();

        $this->get('/bills/search/0/1/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Own fee');
        $this->assertResponseContains('T-Shirt Angels 2026 - S');
        $this->assertResponseContains('/members/view/100');
        $this->assertResponseContains('/bills/view/11');
        $this->assertResponseContains('First101 Last101');
        $this->assertResponseContains('No invoice');
    }

    public function testSearchByLabelKeepsMembersWithoutTheBill(): void
    {
        $this->prepareSearch();

        $this->get('/bills/search/0/1/1?label=shirt');

        $this->assertResponseOk();
        $this->assertResponseContains('T-Shirt Angels 2026 - S');
        $this->assertResponseNotContains('Own fee');
        // Member 1 has no shirt bill, member 101 no bill at all: both still listed
        $this->assertResponseContains('/members/view/1"');
        $this->assertResponseContains('First101 Last101');
        $this->assertSame(2, substr_count((string)$this->_response->getBody(), 'No invoice'));
        // The filter links keep the searched text
        $this->assertResponseContains('/bills/search/0/2/1?label=shirt');
    }

    /**
     * @return array<array{string}>
     */
    public static function labelSearches(): array
    {
        return [
            'upper case' => ['SHIRT'], 'mixed case' => ['t-sHiRt'], 'middle of a word' => ['irt ang'],
            'words in any order' => ['angels shirt'], 'spaces around' => ['  shirt   2026  '],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('labelSearches')]
    public function testSearchLabelPartialAndCaseInsensitive(string $search): void
    {
        $this->prepareSearch();

        $this->get('/bills/search/0/1/1?' . http_build_query(['label' => $search]));

        $this->assertResponseOk();
        $this->assertResponseContains('/bills/view/11');
        $this->assertResponseNotContains('/bills/view/10');
    }

    public function testSearchLabelWithAWordNotFound(): void
    {
        $this->prepareSearch();

        $this->get('/bills/search/0/1/1?label=shirt+hoodie');

        $this->assertResponseNotContains('/bills/view/11');
        $this->assertResponseContains('First100 Last100');
    }

    /**
     * Bill 10: '50% off size_xl', bill 11: 'Shirt off! sizeAxl'
     * Not escaped, '_' and '%' would match any character(s), '!' would escape the next one
     *
     * @return array<array{string, bool, bool}> search, bill 10 found, bill 11 found
     */
    public static function wildcardSearches(): array
    {
        return [
            'percent' => ['%', true, false],
            'underscore' => ['_', true, false],
            'exclamation' => ['!', false, true],
            'percent after a number' => ['50%', true, false],
            'underscore is not any character' => ['size_xl', true, false],
            'exclamation in a word' => ['off!', false, true],
            'percent is not any characters' => ['f%x', false, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('wildcardSearches')]
    public function testSearchLabelWildcardsAreLiteral(string $search, bool $found10, bool $found11): void
    {
        $this->prepareSearch();
        $bills = $this->getTableLocator()->get('Bills');
        $bills->updateAll(['label' => '50% off size_xl'], ['id' => 10]);
        $bills->updateAll(['label' => 'Shirt off! sizeAxl'], ['id' => 11]);

        $this->get('/bills/search/0/1/1?' . http_build_query(['label' => $search]));

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertSame($found10, str_contains($body, '/bills/view/10"'));
        $this->assertSame($found11, str_contains($body, '/bills/view/11"'));
    }

    public function testSearchEscapesOnce(): void
    {
        $this->prepareSearch();
        $this->getTableLocator()->get('Bills')->updateAll(['label' => 'Shirt & <Co>'], ['id' => 11]);
        $this->getTableLocator()->get('Members')->updateAll(['last_name' => "O'Neil"], ['id' => 101]);

        $this->get('/bills/search/0/1/1');

        $this->assertResponseContains('Shirt &amp; &lt;Co&gt;');
        $this->assertResponseContains('First101 O&#039;Neil');
        $this->assertResponseNotContains('&amp;amp;');
        $this->assertResponseNotContains('&amp;#039;');
    }

    public function testSearchColumnsAreSortable(): void
    {
        $this->prepareSearch();

        $this->get('/bills/search/0/1/1');

        $this->assertResponseContains('js-sortable');
        $this->assertResponseContains('table-sort.js');
        $this->assertSame(4, substr_count((string)$this->_response->getBody(), 'class="table-sort-btn"'));
        $this->assertResponseContains('data-sort-value="' . substr($this->yearsAgo(0), 0, 10));
    }

    public function testSearchSeasons(): void
    {
        $this->prepareSearch();
        $this->addBill(12, 100, ['label' => 'Last season fee', 'created' => $this->yearsAgo(1)]);
        $this->addBill(13, 100, ['label' => 'Three seasons ago fee', 'created' => $this->yearsAgo(3)]);
        $this->addBill(14, 100, ['label' => 'Ancient fee', 'created' => $this->yearsAgo(10)]);

        $this->get('/bills/search/0/1/1');
        $this->assertResponseContains('Own fee');
        $this->assertResponseNotContains('Last season fee');

        $this->get('/bills/search/0/2/1');
        $this->assertResponseContains('Last season fee');
        $this->assertResponseNotContains('Ancient fee');

        $this->get('/bills/search/0/3/1');
        $this->assertResponseContains('Last season fee');
        $this->assertResponseNotContains('Three seasons ago fee');
        $this->assertResponseNotContains('Ancient fee');

        $this->get('/bills/search/0/0/1');
        $this->assertResponseContains('Ancient fee');
        $this->assertResponseContains('Last season fee');
    }

    public function testSearchAlumni(): void
    {
        $this->prepareSearch();
        $this->addMember(102, [1], ['active' => false]);
        $this->addBill(12, 102, ['label' => 'Alumni fee', 'created' => $this->yearsAgo(0)]);

        $this->get('/bills/search/0/1/1');
        $this->assertResponseNotContains('Alumni fee');
        $this->assertResponseNotContains('First102 Last102');

        $this->get('/bills/search/0/1/2');
        $this->assertResponseContains('Alumni fee');
        $this->assertResponseNotContains('T-Shirt Angels');
        $this->assertResponseNotContains('First101 Last101');
    }

    public function testSearchForbiddenWithoutBillViewAll(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditAll']);

        $this->get('/bills/search');

        $this->assertResponseCode(403);
    }

    public function testOverviewIsArchived(): void
    {
        $this->loginWith(['Admin', 'BillViewAll', 'BillEditAll']);

        $this->get('/bills/overview');
        $this->assertResponseCode(403);

        $this->get('/bills/search');
        $this->assertResponseNotContains('/bills/overview');
        $this->assertResponseContains('/bills/search');
    }
}
