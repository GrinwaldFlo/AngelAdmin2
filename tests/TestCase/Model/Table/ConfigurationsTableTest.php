<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ConfigurationsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ConfigurationsTable Test Case
 */
class ConfigurationsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ConfigurationsTable
     */
    protected $Configurations;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Configurations',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Configurations') ? [] : ['className' => ConfigurationsTable::class];
        $this->Configurations = TableRegistry::getTableLocator()->get('Configurations', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Configurations);

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
