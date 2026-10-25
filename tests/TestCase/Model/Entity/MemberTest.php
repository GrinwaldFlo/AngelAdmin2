<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Member;
use App\Model\Entity\Team;
use Cake\TestSuite\TestCase;

class MemberTest extends TestCase
{
    public function testTeamStrings(): void
    {
        $member = new Member([
            'teams' => [
                new Team(['name' => 'U12', 'active' => true]),
                new Team(['name' => 'Old', 'active' => false]),
                new Team(['name' => 'U14', 'active' => true]),
            ],
        ]);

        $this->assertSame('U12, Old, U14', $member->teamString);
        $this->assertSame('U12, U14', $member->activeTeamString);
    }

    public function testActiveTeamStringOnlyArchived(): void
    {
        $member = new Member(['teams' => [new Team(['name' => 'Old', 'active' => false])]]);

        $this->assertSame('Old', $member->teamString);
        $this->assertSame(__('No team'), $member->activeTeamString);
    }

    public function testNoTeams(): void
    {
        $member = new Member(['teams' => []]);

        $this->assertSame(__('No team'), $member->teamString);
        $this->assertSame(__('No team'), $member->activeTeamString);
    }
}
