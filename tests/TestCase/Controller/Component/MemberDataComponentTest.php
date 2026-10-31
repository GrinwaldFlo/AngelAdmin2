<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\MembersController;
use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Member 100: field types 1 (sort 2) and 2 (sort 1), late bill 10 (20.-), open bill 11, paid late bill 12
 *
 * @uses \App\Controller\Component\MemberDataComponent
 */
class MemberDataComponentTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.Fields', 'app.FieldTypes', 'app.MemberDocs',
        'app.Meetings', 'app.Presences', 'app.MemberField1s',
    ];

    private MembersController $Controller;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        foreach (['FieldTypes', 'Fields', 'MemberDocs', 'Bills', 'Meetings', 'Presences'] as $table) {
            $this->getTableLocator()->get($table)->deleteAll([]);
        }
        $this->getTableLocator()->get('FieldTypes')->insertQuery()
            ->insert(['id', 'label', 'style', 'sort'])
            ->values(['id' => 1, 'label' => 'Second', 'style' => 0, 'sort' => 2])
            ->values(['id' => 2, 'label' => 'First', 'style' => 0, 'sort' => 1])
            ->execute();
        $this->getTableLocator()->get('MemberDocs')->insertQuery()
            ->insert(['name', 'title', 'created', 'modified'])
            ->values(['name' => 'medical', 'title' => 'Medical form', 'created' => '2026-01-01', 'modified' => '2026-01-01'])
            ->values(['name' => 'photo', 'title' => 'Photo rights', 'created' => '2026-01-01', 'modified' => '2026-01-01'])
            ->execute();
        $this->addMember(100, [1]);
        $this->addBill(10, 100, ['amount' => 20, 'due_date' => '2020-01-01']);
        $this->addBill(11, 100, ['amount' => 30, 'due_date' => '2099-01-01']);
        $this->addBill(12, 100, ['amount' => 40, 'due_date' => '2020-01-01', 'paid' => true]);
        $this->Controller = $this->controller(MembersController::class, $this->identity(['MemberViewAll']));
    }

    protected function tearDown(): void
    {
        $member = $this->getTableLocator()->get('Members')->get(100);
        @unlink($member->GetDocPath('medical'));
        @rmdir($member->MemberPath);
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function addPresence(int $meetingId, string $date): void
    {
        $this->getTableLocator()->get('Meetings')->insertQuery()
            ->insert(['id', 'meeting_date', 'team_id', 'name'])
            ->values(['id' => $meetingId, 'meeting_date' => $date, 'team_id' => 1, 'name' => 'M' . $meetingId])
            ->execute();
        $this->getTableLocator()->get('Presences')->insertQuery()
            ->insert(['id', 'meeting_id', 'member_id', 'state'])
            ->values(['id' => $meetingId, 'meeting_id' => $meetingId, 'member_id' => 100, 'state' => 1])
            ->execute();
    }

    public function testPrepareMemberViewData(): void
    {
        $this->addPresence(1, '2025-10-01 18:00:00');
        $this->addPresence(2, '2026-02-01 18:00:00');
        $this->addPresence(3, '2026-09-15 18:00:00');
        $member = $this->getTableLocator()->get('Members')->get(100, contain: ['Fields' => ['FieldTypes'], 'Presences' => ['Meetings']]);
        $member->CheckFolder();
        file_put_contents($member->GetDocPath('medical'), 'pdf');

        $data = $this->Controller->MemberData->prepareMemberViewData($member, ['firstDaySeason' => '01.09']);

        $this->assertEquals(20, $data['member']->late_bills_sum);
        // Missing fields are added, sorted
        $this->assertSame(['First', 'Second'], array_map(fn ($field) => $field->field_type->label, $data['member']->fields));
        $this->assertSame(['medical' => ['url' => $member->GetDocUrl('medical'), 'title' => 'Medical form']], $data['files']);
        $this->assertSame('First100 Last100', $data['title']);
        $this->assertSame(['2026-2027', '2025-2026'], array_keys($data['attendance']));
        $this->assertSame([2, 1], array_keys($data['attendance']['2025-2026']));
    }

    public function testPrepareMyPageData(): void
    {
        $data = $this->Controller->MemberData->prepareMyPageData(sha1('member100'), ['email' => 'club@example.com']);

        $this->assertSame(100, $data['member']->id);
        $this->assertEquals(20, $data['member']->late_bills_sum);
        $this->assertSame([], $data['files']);
        $this->assertSame([], $data['attendance']);
        $this->assertSame('club@example.com', $data['contactEmail']);
    }

    public function testPrepareMyPageDataErrors(): void
    {
        $this->assertSame(['error' => 'empty_hash'], $this->Controller->MemberData->prepareMyPageData('', []));

        $data = $this->Controller->MemberData->prepareMyPageData(sha1('unknown'), []);
        $this->assertSame('hash_not_found', $data['error']);
        $this->assertSame('not@found.com', $data['contactEmail']);
    }

    public function testNoLateBill(): void
    {
        $this->getTableLocator()->get('Bills')->updateAll(['paid' => true], ['id' => 10]);

        $this->assertEquals(0, $this->Controller->MemberData->prepareMyPageData(sha1('member100'), [])['member']->late_bills_sum);
    }

    public function testMigrateMemberFields(): void
    {
        $this->getTableLocator()->get('FieldTypes')->insertQuery()
            ->insert(['id', 'label', 'style', 'sort'])
            ->values(['id' => 3, 'label' => 'Email', 'style' => 1, 'sort' => 3])
            ->execute();
        $this->getTableLocator()->get('MemberField1s')->insertQuery()
            ->insert(['member_id', 'contact1_first_name', 'contact1_last_name', 'contact1_email', 'facebook', 'a_connu_le_club_de'])
            ->values(['member_id' => 100, 'contact1_first_name' => 'Mum', 'contact1_last_name' => 'Muster ', 'contact1_email' => 'mum@example.com', 'facebook' => '', 'a_connu_le_club_de' => ''])
            ->execute();

        $this->Controller->MemberData->migrateMemberFields();

        $fields = $this->getTableLocator()->get('Fields')->find()->where(['member_id' => 100])->orderBy(['field_type_id' => 'ASC'])->all();
        $this->assertSame([1 => 'Mum Muster', 3 => 'mum@example.com'], $fields->combine('field_type_id', 'value')->toArray());
    }
}
