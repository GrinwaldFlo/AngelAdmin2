<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Mailing;
use App\Model\Entity\MailingItem;
use App\Model\Table\MailingsTable;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Teams: 2 Juniors, 3 Seniors, 4 Old (inactive), 12 Minis
 *
 * @uses \App\Model\Table\MailingsTable
 */
class MailingsTableTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Mailings', 'app.MailingItems', 'app.Teams', 'app.TeamsMembers', 'app.Sites'];

    private MailingsTable $Mailings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Mailings = $this->getTableLocator()->get('Mailings');
        $this->addTeam(2, 'Juniors');
        $this->addTeam(3, 'Seniors');
        $this->addTeam(4, 'Old', false);
        $this->addTeam(12, 'Minis');
    }

    public function testValidationDefault(): void
    {
        $this->assertSame([], $this->Mailings->newEntity(['title' => 'Hello {first_name}', 'content' => 'Hi'])->getErrors());
        $this->assertSame(['title', 'content'], array_keys($this->Mailings->newEntity([])->getErrors()));
        $this->assertArrayHasKey('maxLength', $this->Mailings->newEntity(['title' => str_repeat('a', 101), 'content' => 'x'])->getError('title'));

        $errors = $this->Mailings->newEntity(['title' => 'Hello {unknown}', 'content' => '{other}'])->getErrors();
        $this->assertStringContainsString('{unknown}', $errors['title']['placeholders']);
        $this->assertStringContainsString('{other}', $errors['content']['placeholders']);
    }

    public function testValidPlaceholders(): void
    {
        $this->assertTrue($this->Mailings->validPlaceholders('No placeholder'));
        $this->assertTrue($this->Mailings->validPlaceholders(null));
        $this->assertIsString($this->Mailings->validPlaceholders('{nope}'));
    }

    public function testNewForTeamsActiveMembersWithEmailOnce(): void
    {
        $this->addMember(100, [2, 3], ['email' => ' a@example.com ']);
        $this->addMember(101, [3]);
        $this->addMember(102, [2], ['active' => false]);
        $this->addMember(103, [2], ['email' => '']);
        $this->addMember(104, [4]);

        $mailing = $this->Mailings->newForTeams(['title' => 'Hello', 'content' => 'Hi'], [2, 3, 4, 999]);

        $this->assertSame([], $mailing->getErrors());
        $this->assertSame('Juniors, Seniors', $mailing->teams);
        $this->assertSame('2,3', $mailing->team_id_list);
        $this->assertSame(Mailing::IN_PROGRESS, $mailing->status);
        $items = array_map(fn ($item) => [$item->member_id, $item->email, $item->status], $mailing->mailing_items);
        $this->assertSame([[100, 'a@example.com', MailingItem::PENDING], [101, 'm101@example.com', MailingItem::PENDING]], $items);

        $this->Mailings->saveOrFail($mailing);
        $this->assertSame(2, $this->getTableLocator()->get('MailingItems')->find()->where(['mailing_id' => $mailing->id])->count());
    }

    public function testNewForTeamsErrors(): void
    {
        $this->assertNotEmpty($this->Mailings->newForTeams(['title' => 'a', 'content' => 'b'], [])->getError('team_ids'));
        // Inactive team only
        $this->assertNotEmpty($this->Mailings->newForTeams(['title' => 'a', 'content' => 'b'], [4])->getError('team_ids'));
        // Team without member
        $mailing = $this->Mailings->newForTeams([], [12]);
        $this->assertNotEmpty($mailing->getError('team_ids'));
        $this->assertNotEmpty($mailing->getError('title'));
        $this->assertFalse($this->Mailings->save($mailing));
    }

    public function testCopyKeepsTextAndTeams(): void
    {
        $source = $this->entity(Mailing::class, ['title' => 'Hello', 'content' => 'Hi', 'team_id_list' => '2,12', 'status' => Mailing::DONE]);

        [$mailing, $teamIds] = $this->Mailings->copy($source);

        $this->assertTrue($mailing->isNew());
        $this->assertSame('Hello', $mailing->title);
        $this->assertSame('Hi', $mailing->content);
        $this->assertNull($mailing->status);
        $this->assertSame([2, 12], $teamIds);
    }

    public function testTeamIdsOf(): void
    {
        $this->assertSame([3, 12], $this->Mailings->teamIdsOf($this->entity(Mailing::class, ['team_id_list' => '3,12'])));
        $this->assertSame([], $this->Mailings->teamIdsOf($this->entity(Mailing::class, ['team_id_list' => '', 'teams' => ''])));
        // Old mailings: by the team names
        $teamIds = $this->Mailings->teamIdsOf($this->entity(Mailing::class, ['team_id_list' => '', 'teams' => 'Juniors, Minis, Deleted']));
        sort($teamIds);
        $this->assertSame([2, 12], $teamIds);
    }

    public function testWhereForTeams(): void
    {
        foreach (['A' => '2', 'B' => '12', 'C' => '2,3', 'D' => ''] as $title => $list) {
            $this->Mailings->saveOrFail($this->Mailings->newEntity(['title' => $title, 'content' => 'x', 'team_id_list' => $list]));
        }
        $titles = fn (?array $teamIds) => $this->Mailings->whereForTeams($this->Mailings->find()->orderBy(['title' => 'ASC']), $teamIds)
            ->all()->extract('title')->toList();

        $this->assertSame(['A', 'B', 'C', 'D'], $titles(null));
        $this->assertSame([], $titles([]));
        // 2 must not match 12
        $this->assertSame(['A', 'C'], $titles([2]));
        $this->assertSame(['B', 'C'], $titles([3, 12]));
    }
}
