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
    protected $fixtures = [
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
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test findBills method
     *
     * @return void
     */
    public function testFindBills(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test GetNbOpenInvoice method
     *
     * @return void
     */
    public function testGetNbOpenInvoice(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test GetSumOpenInvoice method
     *
     * @return void
     */
    public function testGetSumOpenInvoice(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test GetSum method
     *
     * @return void
     */
    public function testGetSum(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test GetSumPaidFees method
     *
     * @return void
     */
    public function testGetSumPaidFees(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test GetSumFeesFrom method
     *
     * @return void
     */
    public function testGetSumFeesFrom(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test GetSumInvoicedFrom method
     *
     * @return void
     */
    public function testGetSumInvoicedFrom(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test GetSumInvoicedPaidFrom method
     *
     * @return void
     */
    public function testGetSumInvoicedPaidFrom(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test GetFeeLabel method
     *
     * @return void
     */
    public function testGetFeeLabel(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test CreateMembershipFee method
     *
     * @return void
     */
    public function testCreateMembershipFee(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
