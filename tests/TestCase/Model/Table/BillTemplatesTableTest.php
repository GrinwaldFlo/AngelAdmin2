<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BillTemplatesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BillTemplatesTable Test Case
 */
class BillTemplatesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BillTemplatesTable
     */
    protected $BillTemplates;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.BillTemplates',
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
        $config = TableRegistry::getTableLocator()->exists('BillTemplates') ? [] : ['className' => BillTemplatesTable::class];
        $this->BillTemplates = TableRegistry::getTableLocator()->get('BillTemplates', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BillTemplates);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->BillTemplates->newEntity([
            'label' => str_repeat('a', 201),
            'amount' => '',
            'membership_fee' => 'not-boolean',
            'site_id' => 1,
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('label', $errors);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertArrayHasKey('membership_fee', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->BillTemplates->newEntity([
            'label' => 'Test label',
            'amount' => 125,
            'membership_fee' => true,
            'site_id' => 999999,
        ]);

        $this->assertFalse($this->BillTemplates->checkRules($entity));
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('site_id', $entity->getErrors());
    }
}
