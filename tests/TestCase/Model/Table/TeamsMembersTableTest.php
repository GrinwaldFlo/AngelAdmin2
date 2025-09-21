<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TeamsMembersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TeamsMembersTable Test Case
 */
class TeamsMembersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TeamsMembersTable
     */
    protected $TeamsMembers;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.TeamsMembers',
        'app.Teams',
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
        $config = TableRegistry::getTableLocator()->exists('TeamsMembers') ? [] : ['className' => TeamsMembersTable::class];
        $this->TeamsMembers = TableRegistry::getTableLocator()->get('TeamsMembers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TeamsMembers);

        parent::tearDown();
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
