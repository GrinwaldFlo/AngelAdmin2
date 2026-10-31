<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\User;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * The identity is user 1
 *
 * @uses \App\Policy\UserPolicy
 */
class UserPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles'];

    private function user(int $id): User
    {
        return $this->entity(User::class, ['id' => $id]);
    }

    public function testViewOwnUserOnly(): void
    {
        $user = $this->identity(['MemberViewAll', 'MemberEditAll']);

        $this->assertTrue($user->can('view', $this->user(1)));
        $this->assertFalse($user->can('view', $this->user(2)));
        $this->assertFalse($user->can('viewAll', $this->user(1)));
    }

    public function testEditOwnUserNeedsEditOwn(): void
    {
        $this->assertFalse($this->identity(['MemberEditAll'])->can('edit', $this->user(1)));
        $this->assertTrue($this->identity(['MemberEditOwn'])->can('edit', $this->user(1)));
        $this->assertFalse($this->identity(['MemberEditOwn'])->can('edit', $this->user(2)));
    }

    public function testAdminCanDoAll(): void
    {
        $user = $this->identity(['Admin']);

        foreach (['view', 'viewAll', 'edit', 'admin'] as $action) {
            $this->assertTrue($user->can($action, $this->user(2)), $action);
        }
        $this->assertFalse($this->identity(['MemberEditAll'])->can('admin', $this->user(1)));
    }
}
