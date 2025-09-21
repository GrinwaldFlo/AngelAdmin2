<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PresencesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PresencesTable Test Case
 */
class PresencesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PresencesTable
     */
    protected $Presences;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Presences',
        'app.Meetings',
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
        $config = TableRegistry::getTableLocator()->exists('Presences') ? [] : ['className' => PresencesTable::class];
        $this->Presences = TableRegistry::getTableLocator()->get('Presences', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Presences);

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
