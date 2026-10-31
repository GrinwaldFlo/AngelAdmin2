<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\MemberField1;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Fields of a member: same rules as the member itself (MemberPolicy).
 * Member 1 coaches team 2 (member 100), not team 3 (member 101)
 *
 * @uses \App\Policy\MemberField1Policy
 */
class MemberField1PolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->addCoachedTeams();
    }

    private function fields(int $memberId): MemberField1
    {
        return $this->entity(MemberField1::class, ['member_id' => $memberId]);
    }

    public function testViewOwnOrAll(): void
    {
        $this->assertTrue($this->identity([])->can('view', $this->fields(1)));
        $this->assertFalse($this->identity([])->can('view', $this->fields(101)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('view', $this->fields(101)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('viewAll', $this->fields(101)));
    }

    public function testEditOwnOrAll(): void
    {
        $this->assertFalse($this->identity([])->can('edit', $this->fields(1)));
        $this->assertTrue($this->identity(['MemberEditOwn'])->can('edit', $this->fields(1)));
        $this->assertFalse($this->identity(['MemberEditOwn'])->can('edit', $this->fields(101)));
        $this->assertTrue($this->identity(['MemberEditAll'])->can('edit', $this->fields(101)));
    }

    public function testCoachOnlyMembersOfItsTeams(): void
    {
        $user = $this->identity(['MemberViewAll', 'MemberEditAll', 'TeamScoped']);

        $this->assertTrue($user->can('view', $this->fields(100)));
        $this->assertFalse($user->can('view', $this->fields(101)));
        $this->assertTrue($user->can('edit', $this->fields(100)));
        $this->assertFalse($user->can('edit', $this->fields(101)));
    }

    public function testAdmin(): void
    {
        $this->assertTrue($this->identity(['Admin'])->can('admin', $this->fields(1)));
        $this->assertFalse($this->identity(['MemberEditAll'])->can('admin', $this->fields(1)));
    }
}
