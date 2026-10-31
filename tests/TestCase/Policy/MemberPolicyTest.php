<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Member;
use App\Policy\MemberPolicy;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2 (member 100), not team 3 (member 101)
 *
 * @uses \App\Policy\MemberPolicy
 */
class MemberPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->addCoachedTeams();
    }

    private function member(int $id): Member
    {
        return $this->entity(Member::class, ['id' => $id]);
    }

    public function testNoIdentityIsAlwaysRefused(): void
    {
        $policy = new MemberPolicy();
        $member = $this->member(1);

        foreach (['canViewAll', 'canEdit', 'canEditAll', 'canViewNew', 'canSetSite', 'canAdmin', 'canView'] as $method) {
            $this->assertFalse($policy->$method(null, $member), $method);
        }
    }

    public function testViewOwnWithoutAnyRight(): void
    {
        $user = $this->identity([]);

        $this->assertTrue($user->can('view', $this->member(1)));
        $this->assertFalse($user->can('view', $this->member(100)));
        $this->assertFalse($user->can('viewAll', $this->member(1)));
    }

    public function testViewAll(): void
    {
        $user = $this->identity(['MemberViewAll']);

        $this->assertTrue($user->can('viewAll', $this->member(1)));
        $this->assertTrue($user->can('view', $this->member(101)));
        $this->assertTrue($user->can('viewNew', $this->member(101)));
    }

    public function testCoachViewsOnlyMembersOfItsTeams(): void
    {
        $user = $this->identity(['MemberViewAll', 'TeamScoped']);

        $this->assertTrue($user->can('view', $this->member(100)));
        $this->assertFalse($user->can('view', $this->member(101)));
        $this->assertTrue($user->can('view', $this->member(1)));
        // Members without team are not for the coaches
        $this->assertFalse($user->can('viewNew', $this->member(100)));
    }

    public function testEditOwnNeedsEditOwn(): void
    {
        $this->assertFalse($this->identity([])->can('edit', $this->member(1)));
        $this->assertTrue($this->identity(['MemberEditOwn'])->can('edit', $this->member(1)));
        $this->assertFalse($this->identity(['MemberEditOwn'])->can('edit', $this->member(100)));
    }

    public function testEditAll(): void
    {
        $user = $this->identity(['MemberEditAll']);

        $this->assertTrue($user->can('edit', $this->member(101)));
        $this->assertTrue($user->can('editAll', $this->member(101)));
        // Own member needs MemberEditOwn or an access to its teams: member 1 is in team 1, no restriction here
        $this->assertTrue($user->can('edit', $this->member(1)));
    }

    public function testCoachEditsOnlyMembersOfItsTeams(): void
    {
        $user = $this->identity(['MemberEditAll', 'TeamScoped']);

        $this->assertTrue($user->can('edit', $this->member(100)));
        $this->assertFalse($user->can('edit', $this->member(101)));
        $this->assertFalse($user->can('edit', $this->member(103)));
        // A coach can create a member, its teams are checked afterwards
        $this->assertTrue($user->can('edit', $this->entity(Member::class, [], true)));
        $this->assertFalse($user->can('editAll', $this->member(100)));
    }

    public function testSetSiteWithMemberOrBillViewAll(): void
    {
        $this->assertFalse($this->identity(['MemberEditOwn'])->can('setSite', $this->member(1)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('setSite', $this->member(1)));
        $this->assertTrue($this->identity(['BillViewAll'])->can('setSite', $this->member(1)));
    }

    public function testAdmin(): void
    {
        $this->assertFalse($this->identity(['MemberViewAll', 'MemberEditAll'])->can('admin', $this->member(1)));
        $this->assertTrue($this->identity(['Admin'])->can('admin', $this->member(1)));
    }
}
