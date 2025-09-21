<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SitesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SitesTable Test Case
 */
class SitesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SitesTable
     */
    protected $Sites;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
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
        $config = TableRegistry::getTableLocator()->exists('Sites') ? [] : ['className' => SitesTable::class];
        $this->Sites = TableRegistry::getTableLocator()->get('Sites', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Sites);

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
