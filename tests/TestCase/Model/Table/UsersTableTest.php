<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersTable Test Case
 */
class UsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
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
        $config = TableRegistry::getTableLocator()->exists('Users') ? [] : ['className' => UsersTable::class];
        $this->Users = TableRegistry::getTableLocator()->get('Users', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Users);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Users->newEntity([
            'username' => str_repeat('a', 51),
            'password' => str_repeat('b', 51),
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->Users->newEntity([
            'username' => 'Lorem ipsum dolor sit amet',
            'password' => 'secret',
            'role_id' => 1,
            'member_id' => 1,
            'pass_key' => 'pass-key',
            'tokenhash' => 'token-hash',
        ]);

        $this->assertFalse((bool)$this->Users->save($entity));
        $this->assertNotEmpty($entity->getErrors());
    }

    public function testNewResetTokenIsRandom(): void
    {
        $token = UsersTable::newResetToken();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $token);
        $this->assertNotSame($token, UsersTable::newResetToken());
    }

    public function testValidationPassword(): void
    {
        $this->assertSame([], $this->Users->newEntity(['username' => 'ana', 'password' => 'long enough'], ['validate' => 'password'])->getErrors());
        $this->assertArrayHasKey('password', $this->Users->newEntity(['username' => 'ana'], ['validate' => 'password'])->getErrors());
        $this->assertArrayHasKey('password', $this->Users->newEntity(['password' => ''], ['validate' => 'password'])->getErrors());
        $this->assertArrayHasKey('minLength', $this->Users->newEntity(['password' => 'short'], ['validate' => 'password'])->getError('password'));
    }
}
