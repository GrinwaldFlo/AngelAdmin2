<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FieldTypesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FieldTypesTable Test Case
 */
class FieldTypesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FieldTypesTable
     */
    protected $FieldTypes;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.FieldTypes',
        'app.Fields',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('FieldTypes') ? [] : ['className' => FieldTypesTable::class];
        $this->FieldTypes = TableRegistry::getTableLocator()->get('FieldTypes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->FieldTypes);

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
}
