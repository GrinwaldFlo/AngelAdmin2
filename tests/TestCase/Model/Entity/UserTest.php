<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\User;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\User
 */
class UserTest extends TestCase
{
    protected array $fixtures = ['app.Roles'];

    public function testPasswordIsHashed(): void
    {
        $user = new User(['password' => 'secret']);

        $this->assertNotSame('secret', $user->password);
        $this->assertTrue((new DefaultPasswordHasher())->check('secret', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function testRoleIsLoadedOnce(): void
    {
        $user = new User(['role_id' => 1]);

        $this->assertSame(1, $user->Role->id);
        $this->getTableLocator()->get('Roles')->updateAll(['name' => 'Changed'], ['id' => 1]);
        $this->assertNotSame('Changed', $user->Role->name);
    }
}
