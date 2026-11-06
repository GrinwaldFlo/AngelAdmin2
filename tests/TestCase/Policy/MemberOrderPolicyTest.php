<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\MemberOrder;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * The shop is managed by the merchandising managers, the admins and the roles editing all the members, not by the coaches
 *
 * @uses \App\Policy\MemberOrderPolicy
 */
class MemberOrderPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles'];

    /**
     * Every action of the controller has a rule
     */
    private const ACTIONS = ['viewAll', 'view', 'markDelivered', 'markNotDelivered', 'changeAttribute', 'delete'];

    public function testAllowedWithMemberEditAll(): void
    {
        $user = $this->identity(['MemberEditAll']);

        foreach (self::ACTIONS as $action) {
            $this->assertTrue($user->can($action, new MemberOrder()), $action);
        }
    }

    public function testRefusedToCoachesAndViewers(): void
    {
        foreach ([['MemberEditAll', 'TeamScoped'], ['MemberViewAll', 'MemberEditOwn', 'BillEditAll']] as $rights) {
            $user = $this->identity($rights);
            foreach (self::ACTIONS as $action) {
                $this->assertFalse($user->can($action, new MemberOrder()), $action);
            }
        }
    }

    /**
     * Merchandising manager and admin manage the shop without the members rights
     */
    public function testAllowedToTheMerchandisingManagerAndAdmin(): void
    {
        foreach ([['ShopManager'], ['Admin']] as $rights) {
            $user = $this->identity($rights);
            foreach (self::ACTIONS as $action) {
                $this->assertTrue($user->can($action, new MemberOrder()), $action);
            }
        }
    }
}
