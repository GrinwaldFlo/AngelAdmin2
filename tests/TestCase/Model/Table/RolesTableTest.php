<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RolesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RolesTable Test Case
 */
class RolesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RolesTable
     */
    protected $Roles;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Roles',
        'app.Users',
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
        $config = TableRegistry::getTableLocator()->exists('Roles') ? [] : ['className' => RolesTable::class];
        $this->Roles = TableRegistry::getTableLocator()->get('Roles', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Roles);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Roles->newEntity([
            'name' => '',
            'MemberViewAll' => 'invalid',
            'MemberEditAll' => 'invalid',
            'MemberEditOwn' => 'invalid',
            'BillViewAll' => 'invalid',
            'BillEditAll' => 'invalid',
            'BillValidate' => 'invalid',
            'Editor' => 'invalid',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('MemberViewAll', $errors);
        $this->assertArrayHasKey('MemberEditAll', $errors);
        $this->assertArrayHasKey('MemberEditOwn', $errors);
        $this->assertArrayHasKey('BillViewAll', $errors);
        $this->assertArrayHasKey('BillEditAll', $errors);
        $this->assertArrayHasKey('BillValidate', $errors);
        $this->assertArrayHasKey('Editor', $errors);
    }
}
