<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Mailing;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2, not team 3
 *
 * @uses \App\Policy\MailingPolicy
 */
class MailingPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Mailings', 'app.MailingItems',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->addCoachedTeams();
    }

    private function mailing(string $teamIdList, string $teams = ''): Mailing
    {
        return $this->entity(Mailing::class, ['team_id_list' => $teamIdList, 'teams' => $teams]);
    }

    public function testSendNeedsAdminOrEditAll(): void
    {
        $this->assertFalse($this->identity(['MemberViewAll'])->can('send', $this->mailing('3')));
        $this->assertTrue($this->identity(['MemberEditAll'])->can('send', $this->mailing('3')));
        $this->assertTrue($this->identity(['Admin'])->can('send', $this->mailing('3')));
    }

    public function testManageAnyMailingWhenNotScoped(): void
    {
        $this->assertTrue($this->identity(['MemberEditAll'])->can('manage', $this->mailing('2,3')));
        $this->assertFalse($this->identity(['MemberViewAll'])->can('manage', $this->mailing('2')));
    }

    public function testCoachManagesOnlyMailingsOfItsTeams(): void
    {
        $user = $this->identity(['MemberEditAll', 'TeamScoped']);

        $this->assertTrue($user->can('manage', $this->mailing('2')));
        $this->assertFalse($user->can('manage', $this->mailing('3')));
        $this->assertFalse($user->can('manage', $this->mailing('2,3')));
        // Old mailing without ids: teams found by their names
        $this->assertTrue($user->can('manage', $this->mailing('', 'Juniors')));
        $this->assertFalse($user->can('manage', $this->mailing('', 'Seniors')));
        // Without any team
        $this->assertFalse($user->can('manage', $this->mailing('')));
    }
}
