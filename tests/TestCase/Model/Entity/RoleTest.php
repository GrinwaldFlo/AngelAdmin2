<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Role;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\Role
 */
class RoleTest extends TestCase
{
    public function testIsScoped(): void
    {
        $this->assertFalse((new Role(['TeamScoped' => false, 'Admin' => false]))->isScoped());
        $this->assertTrue((new Role(['TeamScoped' => true, 'Admin' => false]))->isScoped());
        // An admin always has full access
        $this->assertFalse((new Role(['TeamScoped' => true, 'Admin' => true]))->isScoped());
        $this->assertFalse((new Role())->isScoped());
    }
}
