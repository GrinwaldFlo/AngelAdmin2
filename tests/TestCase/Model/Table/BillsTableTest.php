<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BillsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BillsTable Test Case
 */
class BillsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BillsTable
     */
    protected $Bills;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Bills',
        'app.Members',
        'app.Sites',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Bills') ? [] : ['className' => BillsTable::class];
        $this->Bills = TableRegistry::getTableLocator()->get('Bills', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Bills);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Bills->newEntity([]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('label', $errors);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertArrayHasKey('printed', $errors);
        $this->assertArrayHasKey('paid', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->Bills->newEntity([
            'member_id' => 9999,
            'site_id' => 9999,
            'label' => 'Test invoice',
            'amount' => 150,
            'printed' => false,
            'paid' => false,
            'reminder' => 0,
            'due_date' => '2026-01-01',
            'due_date_ori' => '2026-01-01',
            'link_membership_fee' => false,
            'canceled' => false,
            'state_id' => 1,
            'tokenhash' => 'test-token',
        ]);

        $this->assertFalse((bool)$this->Bills->save($entity));
        $this->assertNotEmpty($entity->getErrors());
    }

    /**
     * Test findBills method
     *
     * @return void
     */
    public function testFindBills(): void
    {
        $result = $this->Bills->find('bills', billStatus: 3, memberStatus: 1, teamId: 0)->all();

        $this->assertCount(1, $result);
    }

    /**
     * Test GetNbOpenInvoice method
     *
     * @return void
     */
    public function testGetNbOpenInvoice(): void
    {
        $this->assertSame(0, $this->Bills->GetNbOpenInvoice());
    }

    /**
     * Test GetSumOpenInvoice method
     *
     * @return void
     */
    public function testGetSumOpenInvoice(): void
    {
        $this->assertNull($this->Bills->GetSumOpenInvoice());
    }

    /**
     * Test GetSum method
     *
     * @return void
     */
    public function testGetSum(): void
    {
        $sum = $this->Bills->GetSum(['Bills.paid =' => 1]);

        $this->assertSame(1, (int)$sum);
    }

    /**
     * Test GetSumPaidFees method
     *
     * @return void
     */
    public function testGetSumPaidFees(): void
    {
        $sum = $this->Bills->GetSumPaidFees('2020-01-01');

        $this->assertSame(0, (int)$sum);
    }

    /**
     * Test GetSumFeesFrom method
     *
     * @return void
     */
    public function testGetSumFeesFrom(): void
    {
        $sum = $this->Bills->GetSumFeesFrom('2020-01-01');

        $this->assertSame(0, (int)$sum);
    }

    /**
     * Test GetSumInvoicedFrom method
     *
     * @return void
     */
    public function testGetSumInvoicedFrom(): void
    {
        $sum = $this->Bills->GetSumInvoicedFrom('2020-01-01');

        $this->assertSame(0, (int)$sum);
    }

    /**
     * Test GetSumInvoicedPaidFrom method
     *
     * @return void
     */
    public function testGetSumInvoicedPaidFrom(): void
    {
        $sum = $this->Bills->GetSumInvoicedPaidFrom('2020-01-01');

        $this->assertSame(0, (int)$sum);
    }

    /**
     * Test GetFeeLabel method
     *
     * @return void
     */
    public function testGetFeeLabel(): void
    {
        $label = $this->Bills->GetFeeLabel([
            'feeLabel' => 'Membership Fee',
            'year' => 2025,
        ]);

        $this->assertSame('Membership Fee 2025-2026', $label);
    }

    /**
     * Test CreateMembershipFee method
     *
     * @return void
     */
    public function testCreateMembershipFee(): void
    {
        $member = $this->Bills->Members->get(1);
        $before = $this->Bills->find()->count();

        $this->Bills->CreateMembershipFee(
            ['feeLabel' => 'Membership Fee', 'year' => 2025],
            $member,
            'Extra',
            '2026-01-15',
            250
        );

        $after = $this->Bills->find()->count();
        $bill = $this->Bills->find()->orderBy(['id' => 'DESC'])->first();

        $this->assertSame($before + 1, $after);
        $this->assertSame('Membership Fee 2025-2026 Extra', $bill->label);
        $this->assertSame(250, $bill->amount);
    }
}
