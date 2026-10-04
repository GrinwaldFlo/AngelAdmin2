<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FieldsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FieldsTable Test Case
 */
class FieldsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FieldsTable
     */
    protected $Fields;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Fields',
        'app.Members',
        'app.FieldTypes',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Fields') ? [] : ['className' => FieldsTable::class];
        $this->Fields = TableRegistry::getTableLocator()->get('Fields', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Fields);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Fields->newEntity([
            'member_id' => 1,
            'field_type_id' => 1,
            'value' => str_repeat('a', 256),
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('value', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->Fields->newEntity([
            'member_id' => 1,
            'field_type_id' => 1,
            'value' => 'Valid value',
        ]);

        $this->assertTrue($this->Fields->checkRules($entity));
        $this->assertEmpty($entity->getErrors());
    }
}
