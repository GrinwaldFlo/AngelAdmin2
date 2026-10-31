<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Meeting;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2, not team 3
 *
 * @uses \App\Policy\MeetingPolicy
 */
class MeetingPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->addCoachedTeams();
    }

    private function meeting(?int $teamId, bool $new = false): Meeting
    {
        return $this->entity(Meeting::class, ['team_id' => $teamId], $new);
    }

    public function testViewNeedsMemberViewAll(): void
    {
        $this->assertFalse($this->identity([])->can('view', $this->meeting(3)));
        $this->assertFalse($this->identity([])->can('viewAll', $this->meeting(3)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('view', $this->meeting(3)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('setPresence', $this->meeting(3)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('viewAll', $this->meeting(3)));
    }

    public function testCoachViewsOnlyMeetingsOfItsTeams(): void
    {
        $user = $this->identity(['MemberViewAll', 'TeamScoped']);

        $this->assertTrue($user->can('view', $this->meeting(2)));
        $this->assertTrue($user->can('setPresence', $this->meeting(2)));
        $this->assertFalse($user->can('view', $this->meeting(3)));
        $this->assertFalse($user->can('setPresence', $this->meeting(3)));
    }

    public function testEdit(): void
    {
        $this->assertFalse($this->identity(['MemberViewAll'])->can('edit', $this->meeting(3)));
        $this->assertTrue($this->identity(['MemberEditAll'])->can('edit', $this->meeting(3)));
    }

    public function testCoachEditsOnlyMeetingsOfItsTeams(): void
    {
        $user = $this->identity(['MemberEditAll', 'TeamScoped']);

        $this->assertTrue($user->can('edit', $this->meeting(2)));
        $this->assertFalse($user->can('edit', $this->meeting(3)));
        // New meeting: its team is checked once set
        $this->assertTrue($user->can('edit', $this->meeting(null, true)));
        $this->assertFalse($user->can('edit', $this->meeting(3, true)));
        // Saved meeting without team: refused
        $this->assertFalse($user->can('edit', $this->meeting(null)));
    }

    public function testJoinIsOpenToAll(): void
    {
        $this->assertTrue($this->identity([])->can('join', $this->meeting(3)));
    }

    public function testAdmin(): void
    {
        $this->assertTrue($this->identity(['Admin'])->can('admin', $this->meeting(3)));
        $this->assertFalse($this->identity(['MemberEditAll'])->can('admin', $this->meeting(3)));
    }
}
