<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\TeamsCoach;
use App\Policy\TeamScope;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2, not team 3 (see addCoachedTeams())
 *
 * @uses \App\Policy\TeamScope
 */
class TeamScopeTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->addCoachedTeams();
    }

    public function testNoUser(): void
    {
        $this->assertFalse(TeamScope::isScoped(null));
        $this->assertFalse(TeamScope::canUnscoped(null, 'MemberViewAll'));
        $this->assertNull(TeamScope::teamIds(null));
        $this->assertTrue(TeamScope::canAccessTeam(null, 3));
    }

    public function testAdminIsNeverScoped(): void
    {
        $user = $this->identity(['Admin', 'TeamScoped', 'MemberViewAll']);

        $this->assertFalse(TeamScope::isScoped($user));
        $this->assertTrue(TeamScope::canUnscoped($user, 'MemberViewAll'));
        $this->assertNull(TeamScope::teamIds($user));
    }

    public function testUnscopedRoleAccessesEverything(): void
    {
        $user = $this->identity(['MemberViewAll']);

        $this->assertFalse(TeamScope::isScoped($user));
        $this->assertTrue(TeamScope::canUnscoped($user, 'MemberViewAll'));
        $this->assertFalse(TeamScope::canUnscoped($user, 'MemberEditAll'));
        $this->assertTrue(TeamScope::canAccessTeam($user, 3));
        $this->assertTrue(TeamScope::canAccessTeam($user, null));
        $this->assertTrue(TeamScope::canAccessAllTeams($user, []));
        $this->assertTrue(TeamScope::canAccessMember($user, 101));
    }

    public function testScopedRoleOnlyCoachedTeams(): void
    {
        $user = $this->identity(['MemberViewAll', 'TeamScoped']);

        $this->assertTrue(TeamScope::isScoped($user));
        $this->assertFalse(TeamScope::canUnscoped($user, 'MemberViewAll'));
        $this->assertSame([2], TeamScope::teamIds($user));
        $this->assertTrue(TeamScope::canAccessTeam($user, 2));
        $this->assertFalse(TeamScope::canAccessTeam($user, 3));
        $this->assertFalse(TeamScope::canAccessTeam($user, null));
    }

    public function testScopedCanAccessAllTeams(): void
    {
        $user = $this->identity(['MemberViewAll', 'TeamScoped']);

        $this->assertTrue(TeamScope::canAccessAllTeams($user, [2]));
        $this->assertFalse(TeamScope::canAccessAllTeams($user, [2, 3]));
        // No team at all: nothing proves the coach may access it
        $this->assertFalse(TeamScope::canAccessAllTeams($user, []));
    }

    public function testScopedCanAccessOnlyActiveMembersOfCoachedTeams(): void
    {
        $user = $this->identity(['MemberViewAll', 'TeamScoped']);

        $this->assertTrue(TeamScope::canAccessMember($user, 100));
        $this->assertFalse(TeamScope::canAccessMember($user, 101));
        $this->assertTrue(TeamScope::canAccessMember($user, 102));
        $this->assertFalse(TeamScope::canAccessMember($user, 103));
        $this->assertFalse(TeamScope::canAccessMember($user, null));
    }

    public function testHelpCoachHasTheSameScope(): void
    {
        $this->addTeam(4);
        $this->addCoach(4, 1, TeamsCoach::HELP_COACH);

        $this->assertSame([2, 4], TeamScope::teamIds($this->identity(['MemberViewAll', 'TeamScoped'])));
    }

    public function testScopedWithoutTeamAccessesNothing(): void
    {
        $user = $this->identity(['MemberViewAll', 'TeamScoped'], 100);

        $this->assertSame([], TeamScope::teamIds($user));
        $this->assertFalse(TeamScope::canAccessTeam($user, 2));
        $this->assertFalse(TeamScope::canAccessMember($user, 100));
    }
}
