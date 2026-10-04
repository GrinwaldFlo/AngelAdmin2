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
    protected array $fixtures = [
        'app.FieldTypes',
        'app.Fields',
        'app.Members',
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
        $entity = $this->FieldTypes->newEntity([
            'label' => '',
            'style' => 'not-an-int',
            'member_edit' => 'not-an-int',
            'hidden' => 'not-an-int',
            'mandatory' => 'not-an-int',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('label', $errors);
        $this->assertArrayHasKey('style', $errors);
        $this->assertArrayHasKey('member_edit', $errors);
        $this->assertArrayHasKey('hidden', $errors);
        $this->assertArrayHasKey('mandatory', $errors);
    }
}
