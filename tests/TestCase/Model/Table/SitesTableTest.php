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
    protected array $fixtures = [
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
        $entity = $this->Sites->newEntity([
            'city' => '',
            'feeMax' => '',
            'sender_email' => '',
            'sender' => '',
            'add_invoice_num' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('city', $errors);
        $this->assertArrayHasKey('feeMax', $errors);
        $this->assertArrayHasKey('sender_email', $errors);
        $this->assertArrayHasKey('sender', $errors);
        $this->assertArrayHasKey('add_invoice_num', $errors);
    }
}
