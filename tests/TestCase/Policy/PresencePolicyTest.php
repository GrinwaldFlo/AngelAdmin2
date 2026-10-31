<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Presence;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2 (member 100), not team 3 (member 101)
 *
 * @uses \App\Policy\PresencePolicy
 */
class PresencePolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->addCoachedTeams();
    }

    private function presence(int $memberId): Presence
    {
        return $this->entity(Presence::class, ['member_id' => $memberId, 'meeting_id' => 1]);
    }

    public function testRights(): void
    {
        $this->assertFalse($this->identity([])->can('view', $this->presence(101)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('view', $this->presence(101)));
        $this->assertFalse($this->identity(['MemberViewAll'])->can('edit', $this->presence(101)));
        $this->assertFalse($this->identity(['MemberViewAll'])->can('viewAll', $this->presence(101)));
        $this->assertTrue($this->identity(['MemberEditAll'])->can('edit', $this->presence(101)));
        $this->assertTrue($this->identity(['MemberEditAll'])->can('viewAll', $this->presence(101)));
        $this->assertTrue($this->identity(['Admin'])->can('admin', $this->presence(101)));
        $this->assertFalse($this->identity(['MemberEditAll'])->can('admin', $this->presence(101)));
    }

    public function testCoachOnlyMembersOfItsTeams(): void
    {
        $user = $this->identity(['MemberViewAll', 'MemberEditAll', 'TeamScoped']);

        $this->assertTrue($user->can('view', $this->presence(100)));
        $this->assertFalse($user->can('view', $this->presence(101)));
        $this->assertTrue($user->can('edit', $this->presence(100)));
        $this->assertFalse($user->can('edit', $this->presence(101)));
    }
}
