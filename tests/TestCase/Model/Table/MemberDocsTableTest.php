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
    protected $fixtures = [
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
}
