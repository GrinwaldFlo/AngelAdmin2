<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RegistrationsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RegistrationsTable Test Case
 */
class RegistrationsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RegistrationsTable
     */
    protected $Registrations;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Registrations',
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
        $config = TableRegistry::getTableLocator()->exists('Registrations') ? [] : ['className' => RegistrationsTable::class];
        $this->Registrations = TableRegistry::getTableLocator()->get('Registrations', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Registrations);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Registrations->newEntity([
            'signature_member' => 'abc',
            'signature_parent' => 'def',
            'member_id' => 1,
            'year' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('year', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->Registrations->newEntity([
            'signature_member' => 'abc',
            'signature_parent' => 'def',
            'member_id' => 999999,
            'year' => 2025,
        ]);

        $this->assertFalse($this->Registrations->checkRules($entity));
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('member_id', $entity->getErrors());
    }
}
