<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\TeamsCoach;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\Team
 */
class TeamTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches'];

    public function testNbMembersAndCoachs(): void
    {
        $this->addTeam(2, 'Juniors');
        $this->addMember(100, [2]);
        $this->addMember(101, [2], ['active' => false]);
        $this->addMember(102, [2]);
        $this->addCoach(2, 100);
        $this->addCoach(2, 102, TeamsCoach::HELP_COACH);

        $team = $this->getTableLocator()->get('Teams')->get(2);

        $this->assertSame(2, $team->NbMembers);
        $this->assertSame([100, 102], array_map(fn ($member) => $member->id, $team->Coachs));
    }
}
