<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\ShopItem;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * The shop is managed by the roles editing all the members, not by the coaches
 *
 * @uses \App\Policy\ShopItemPolicy
 */
class ShopItemPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles'];

    /**
     * Every action of the controller has a rule
     */
    private const ACTIONS = ['viewAll', 'view', 'edit', 'editAll', 'delete'];

    public function testAllowedWithMemberEditAll(): void
    {
        $user = $this->identity(['MemberEditAll']);

        foreach (self::ACTIONS as $action) {
            $this->assertTrue($user->can($action, new ShopItem()), $action);
        }
    }

    public function testRefusedToCoachesAndViewers(): void
    {
        foreach ([['MemberEditAll', 'TeamScoped'], ['MemberViewAll', 'MemberEditOwn', 'BillEditAll']] as $rights) {
            $user = $this->identity($rights);
            foreach (self::ACTIONS as $action) {
                $this->assertFalse($user->can($action, new ShopItem()), $action);
            }
        }
    }

    public function testAdmin(): void
    {
        $this->assertTrue($this->identity(['Admin'])->can('admin', new ShopItem()));
        $this->assertFalse($this->identity(['MemberEditAll'])->can('admin', new ShopItem()));
    }
}
