<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TeamsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TeamsTable Test Case
 */
class TeamsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TeamsTable
     */
    protected $Teams;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Teams',
        'app.Meetings',
        'app.Members',
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
        $config = TableRegistry::getTableLocator()->exists('Teams') ? [] : ['className' => TeamsTable::class];
        $this->Teams = TableRegistry::getTableLocator()->get('Teams', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Teams);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Teams->newEntity([
            'name' => '',
            'membership_fee' => 'not-an-int',
            'active' => 'nope',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('membership_fee', $errors);
        $this->assertArrayHasKey('active', $errors);
    }
}
