<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Bill;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2 (member 100), not team 3 (member 101)
 *
 * @uses \App\Policy\BillPolicy
 */
class BillPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->addCoachedTeams();
    }

    private function bill(int $memberId): Bill
    {
        return $this->entity(Bill::class, ['id' => 1, 'member_id' => $memberId]);
    }

    public function testViewOwnBillWithoutAnyRight(): void
    {
        $user = $this->identity([]);

        $this->assertTrue($user->can('view', $this->bill(1)));
        $this->assertFalse($user->can('view', $this->bill(100)));
    }

    public function testViewAll(): void
    {
        $user = $this->identity(['BillViewAll']);

        $this->assertTrue($user->can('viewAll', $this->bill(1)));
        $this->assertTrue($user->can('overview', $this->bill(1)));
        $this->assertTrue($user->can('view', $this->bill(101)));
        $this->assertFalse($this->identity(['MemberViewAll'])->can('viewAll', $this->bill(1)));
    }

    public function testCoachSeesOnlyBillsOfItsMembersAndNoOverview(): void
    {
        $user = $this->identity(['BillViewAll', 'TeamScoped']);

        $this->assertTrue($user->can('viewAll', $this->bill(1)));
        $this->assertFalse($user->can('overview', $this->bill(1)));
        $this->assertTrue($user->can('view', $this->bill(100)));
        $this->assertFalse($user->can('view', $this->bill(101)));
        $this->assertFalse($user->can('view', $this->bill(103)));
    }

    public function testEditAndAdmin(): void
    {
        $this->assertTrue($this->identity(['BillEditAll'])->can('edit', $this->bill(1)));
        $this->assertFalse($this->identity(['BillViewAll'])->can('edit', $this->bill(1)));
        $this->assertTrue($this->identity(['Admin'])->can('admin', $this->bill(1)));
        $this->assertFalse($this->identity(['BillEditAll'])->can('admin', $this->bill(1)));
    }
}
