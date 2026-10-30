<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\TeamsCoach;
use App\Model\Entity\User;
use App\Model\Table\TeamsCoachesTable;
use App\Policy\TeamScope;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\TeamsCoachesTable
 * @uses \App\Policy\TeamScope
 * @uses \App\Model\Table\MailingsTable::whereForTeams()
 */
class TeamsCoachesTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Members',
        'app.Users',
        'app.Roles',
        'app.Sites',
        'app.Teams',
        'app.TeamsMembers',
        'app.TeamsCoaches',
        'app.Mailings',
        'app.MailingItems',
    ];

    private TeamsCoachesTable $TeamsCoaches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->TeamsCoaches = $this->getTableLocator()->get('TeamsCoaches');
        foreach ([2 => true, 3 => true, 12 => true, 4 => false] as $id => $active) {
            $this->getTableLocator()->get('Teams')->insertQuery()
                ->insert(['id', 'name', 'membership_fee', 'active', 'site_id'])
                ->values(['id' => $id, 'name' => 'Team' . $id, 'membership_fee' => 1, 'active' => $active, 'site_id' => 1])
                ->execute();
        }
    }

    private function user(bool $teamScoped, bool $admin = false): User
    {
        $this->getTableLocator()->get('Roles')->updateAll(['TeamScoped' => $teamScoped, 'Admin' => $admin], ['id' => 1]);

        return new User(['member_id' => 1, 'role_id' => 1]);
    }

    public function testTeamIdsOfOnlyActiveTeams(): void
    {
        $this->assertFalse($this->TeamsCoaches->addCoach(2, 1, TeamsCoach::COACH)->hasErrors());
        $this->assertFalse($this->TeamsCoaches->addCoach(4, 1, TeamsCoach::HELP_COACH)->hasErrors());

        $this->assertSame([2], $this->TeamsCoaches->teamIdsOf(1));
        $this->assertSame([], $this->TeamsCoaches->teamIdsOf(999));
    }

    public function testAddAndRemoveCoachClearsCache(): void
    {
        $row = $this->TeamsCoaches->addCoach(2, 1, TeamsCoach::COACH);
        $this->assertSame([2], $this->TeamsCoaches->teamIdsOf(1));

        $this->assertTrue($this->TeamsCoaches->delete($row));
        $this->assertSame([], $this->TeamsCoaches->teamIdsOf(1));
    }

    public function testAddCoachRefusesSameMemberTwice(): void
    {
        $this->TeamsCoaches->addCoach(2, 1, TeamsCoach::COACH);

        $row = $this->TeamsCoaches->addCoach(2, 1, TeamsCoach::HELP_COACH);
        $this->assertNotEmpty($row->getError('member_id'));
        // Unchanged
        $this->assertSame([[1, TeamsCoach::COACH]], $this->TeamsCoaches->find()->all()->map(fn ($row) => [$row->member_id, $row->type])->toList());
    }

    public function testAddCoachRefusesInvalidTypeOrMember(): void
    {
        $this->assertNotEmpty($this->TeamsCoaches->addCoach(2, 1, 99)->getError('type'));
        $this->assertNotEmpty($this->TeamsCoaches->addCoach(2, 999, TeamsCoach::COACH)->getError('member_id'));
        $this->assertSame(0, $this->TeamsCoaches->find()->count());
    }

    public function testRowsByTeamCoachesFirst(): void
    {
        $this->getTableLocator()->get('Members')->insertQuery()
            ->insert(['id', 'first_name', 'last_name', 'gender_id', 'email', 'email_valid', 'date_arrival', 'hash', 'active'])
            ->values(['id' => 100, 'first_name' => 'Aaa', 'last_name' => 'Help', 'gender_id' => 1, 'email' => 'a@example.com',
                'email_valid' => 1, 'date_arrival' => '2026-01-01', 'hash' => sha1('100'), 'active' => true])
            ->execute();
        $this->TeamsCoaches->addCoach(2, 100, TeamsCoach::HELP_COACH);
        $this->TeamsCoaches->addCoach(2, 1, TeamsCoach::COACH);

        $rows = $this->TeamsCoaches->rowsByTeam([2, 3]);
        $this->assertSame([1, 100], array_map(fn ($row) => $row->member->id, $rows[2]));
        $this->assertSame([], $rows[3]);
    }

    public function testCoachesOfByType(): void
    {
        $this->TeamsCoaches->addCoach(2, 1, TeamsCoach::HELP_COACH);

        $coaches = $this->TeamsCoaches->coachesOf(2);
        $this->assertSame([], $coaches[TeamsCoach::COACH]);
        $this->assertSame([1], array_map(fn ($member) => $member->id, $coaches[TeamsCoach::HELP_COACH]));
        $this->assertSame([TeamsCoach::COACH => [], TeamsCoach::HELP_COACH => []], $this->TeamsCoaches->coachesOf(3));
    }

    public function testCandidatesOnlyActiveMembersWithAccount(): void
    {
        $this->getTableLocator()->get('Members')->insertQuery()
            ->insert(['id', 'first_name', 'last_name', 'gender_id', 'email', 'email_valid', 'date_arrival', 'hash', 'active'])
            ->values(['id' => 100, 'first_name' => 'No', 'last_name' => 'Account', 'gender_id' => 1, 'email' => 'a@example.com',
                'email_valid' => 1, 'date_arrival' => '2026-01-01', 'hash' => sha1('100'), 'active' => true])
            ->execute();

        $this->assertSame([1], array_keys($this->TeamsCoaches->candidates()));
    }

    public function testTeamScopeAdminIsNeverScoped(): void
    {
        $this->assertNull(TeamScope::teamIds($this->user(true, true)));
        $this->assertNull(TeamScope::teamIds($this->user(false)));
        $this->assertNull(TeamScope::teamIds(null));
        $this->assertTrue(TeamScope::canAccessMember($this->user(false), 999));
    }

    public function testTeamScopeOfCoach(): void
    {
        $this->TeamsCoaches->addCoach(2, 1, TeamsCoach::COACH);
        $coach = $this->user(true);

        $this->assertSame([2], TeamScope::teamIds($coach));
        $this->assertTrue(TeamScope::canAccessTeam($coach, 2));
        $this->assertFalse(TeamScope::canAccessTeam($coach, 3));
        $this->assertTrue(TeamScope::canAccessAllTeams($coach, [2]));
        $this->assertFalse(TeamScope::canAccessAllTeams($coach, [2, 3]));
        $this->assertFalse(TeamScope::canAccessAllTeams($coach, []));
        // Member 1 is in team 1 only (fixture)
        $this->assertFalse(TeamScope::canAccessMember($coach, 1));
        $this->assertFalse(TeamScope::canAccessMember($coach, null));
    }

    public function testMailingsForTeamsDoesNotMatchPartOfAnId(): void
    {
        $mailings = $this->getTableLocator()->get('Mailings');
        foreach (['12' => '12', '2,3' => '2,3', '3' => '3'] as $title => $list) {
            $mailings->saveOrFail($mailings->newEntity(['title' => 'T' . $title, 'content' => 'x', 'team_id_list' => $list, 'teams' => '']));
        }

        $titles = fn (?array $teamIds) => $mailings->whereForTeams($mailings->find(), $teamIds)
            ->orderBy(['title' => 'ASC'])->all()->extract('title')->toList();

        $this->assertSame(['T2,3'], $titles([2]));
        $this->assertSame(['T12', 'T2,3'], $titles([2, 12]));
        $this->assertSame([], $titles([]));
        $this->assertCount(3, $titles(null));
    }
}
