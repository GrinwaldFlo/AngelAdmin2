<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MemberDocsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MemberDocsTable Test Case
 */
class MemberDocsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MemberDocsTable
     */
    protected $MemberDocs;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.MemberDocs',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('MemberDocs') ? [] : ['className' => MemberDocsTable::class];
        $this->MemberDocs = TableRegistry::getTableLocator()->get('MemberDocs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->MemberDocs);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->MemberDocs->newEntity([
            'name' => str_repeat('a', 21),
            'title' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('title', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->MemberDocs->newEntity([
            'name' => 'Unique doc name',
            'title' => 'Unique title',
        ]);

        $this->assertTrue($this->MemberDocs->checkRules($entity));
        $this->assertEmpty($entity->getErrors());
    }
}
