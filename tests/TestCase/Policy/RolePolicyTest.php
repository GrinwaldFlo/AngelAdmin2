<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Role;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Policy\RolePolicy
 */
class RolePolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles'];

    public function testCanAdminOnlyWithAdmin(): void
    {
        $resource = new Role();

        $this->assertTrue($this->identity(['Admin'])->can('admin', $resource));
        $this->assertFalse($this->identity(['MemberViewAll', 'MemberEditAll', 'BillViewAll', 'BillEditAll'])->can('admin', $resource));
    }
}
