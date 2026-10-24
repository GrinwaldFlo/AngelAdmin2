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
    protected array $fixtures = [
        'app.Presences',
        'app.Meetings',
        'app.Members',
        'app.Teams',
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
        $entity = $this->Presences->newEntity([
            'meeting_id' => 1,
            'member_id' => 1,
            'state' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('state', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->Presences->newEntity([
            'meeting_id' => 999999,
            'member_id' => 999999,
            'state' => 1,
        ]);

        $this->assertFalse($this->Presences->checkRules($entity));
        $this->assertNotEmpty($entity->getErrors());
    }
}
