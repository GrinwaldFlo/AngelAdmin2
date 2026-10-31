<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Team;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2, not team 3
 *
 * @uses \App\Policy\TeamPolicy
 */
class TeamPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->addCoachedTeams();
    }

    private function team(int $id): Team
    {
        return $this->entity(Team::class, ['id' => $id]);
    }

    public function testViewAndEdit(): void
    {
        $this->assertFalse($this->identity([])->can('view', $this->team(3)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('viewAll', $this->team(3)));
        $this->assertTrue($this->identity(['MemberViewAll'])->can('view', $this->team(3)));
        $this->assertFalse($this->identity(['MemberViewAll'])->can('edit', $this->team(3)));
        $this->assertTrue($this->identity(['MemberEditAll'])->can('edit', $this->team(3)));
    }

    public function testCoachOnlyItsTeams(): void
    {
        $user = $this->identity(['MemberViewAll', 'MemberEditAll', 'TeamScoped']);

        $this->assertTrue($user->can('viewAll', $this->team(3)));
        $this->assertTrue($user->can('view', $this->team(2)));
        $this->assertFalse($user->can('view', $this->team(3)));
        $this->assertTrue($user->can('edit', $this->team(2)));
        $this->assertFalse($user->can('edit', $this->team(3)));
    }

    public function testAssignCoachesNotForCoaches(): void
    {
        $this->assertTrue($this->identity(['MemberEditAll'])->can('assignCoaches', $this->team(2)));
        $this->assertFalse($this->identity(['MemberEditAll', 'TeamScoped'])->can('assignCoaches', $this->team(2)));
        $this->assertFalse($this->identity(['MemberViewAll'])->can('assignCoaches', $this->team(2)));
    }

    public function testAdmin(): void
    {
        $this->assertTrue($this->identity(['Admin'])->can('admin', $this->team(2)));
        $this->assertFalse($this->identity(['MemberEditAll'])->can('admin', $this->team(2)));
    }
}
