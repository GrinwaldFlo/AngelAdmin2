<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\MemberManagementComponent;
use App\Controller\MembersController;
use App\Controller\PrefResult;
use App\Model\Entity\Member;
use App\Test\TestSuite\TestDataTrait;
use Authorization\Exception\ForbiddenException;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2 (members 100, 102), not team 3 (member 101)
 *
 * @uses \App\Controller\Component\MemberManagementComponent
 */
class MemberManagementComponentTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.Fields', 'app.FieldTypes',
    ];

    private MembersController $Controller;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->addCoachedTeams();
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en'], ['id' => 1]);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function management(array $rights, array $request = []): MemberManagementComponent
    {
        $this->Controller = $this->controller(MembersController::class, $this->identity($rights), $request);

        return $this->Controller->MemberManagement;
    }

    private function flashes(): array
    {
        return array_column($this->Controller->getRequest()->getSession()->read('Flash.flash') ?? [], 'element');
    }

    private function member(int $id): Member
    {
        return $this->getTableLocator()->get('Members')->get($id);
    }

    public function testCreateMemberForm(): void
    {
        $member = $this->management(['MemberEditAll'])->createMember([]);

        $this->assertTrue($member->isNew());
    }

    public function testCreateMember(): void
    {
        $member = $this->management(['MemberEditAll'])->createMember($this->memberForm());

        $this->assertFalse($member->isNew());
        $this->assertTrue(\App\Model\Table\MembersTable::isValidHash($member->hash));
        $this->assertEquals(\Cake\I18n\Date::today(), $member->date_arrival);
        $this->assertSame(['flash/success'], $this->flashes());
    }

    public function testCreateMemberAlreadyExists(): void
    {
        $this->assertNull($this->management(['MemberEditAll'])->createMember($this->memberForm(['first_name' => 'First100', 'last_name' => 'Last100'])));
        $this->assertSame(['flash/error'], $this->flashes());
    }

    public function testCoachIsWarnedTheNewMemberIsInNoneOfItsTeams(): void
    {
        $this->management(['MemberEditAll', 'TeamScoped'])->createMember($this->memberForm());

        $this->assertSame(['flash/success', 'flash/warning'], $this->flashes());
    }

    public function testCreateMemberRefused(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->management(['MemberViewAll'])->createMember(['first_name' => 'New', 'last_name' => 'Member']);
    }

    public function testCreateMemberRefusedToMemberLinks(): void
    {
        $this->Controller = $this->controller(MembersController::class, null, ['cookies' => ['member_hash' => sha1('member100')]]);

        $this->assertSame('redirect_home', $this->Controller->MemberManagement->createMember($this->memberForm()));
        $this->assertSame(0, $this->getTableLocator()->get('Members')->find()->where(['first_name' => 'New'])->count());
    }

    public function testUpdateMember(): void
    {
        $member = $this->management(['MemberEditAll', 'TeamScoped'], ['params' => ['action' => 'edit']])->updateMember(100, ['first_name' => 'Changed']);

        $this->assertSame('Changed', $member->first_name);
        $this->assertSame('Changed', $this->member(100)->first_name);
    }

    public function testUpdateMemberOfAnotherTeamRefusedToCoach(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->management(['MemberEditAll', 'TeamScoped'], ['params' => ['action' => 'edit']])->updateMember(101, ['first_name' => 'Changed']);
    }

    public function testUpdateMemberAllowedWithoutIdentity(): void
    {
        $this->Controller = $this->controller(MembersController::class);

        $this->Controller->MemberManagement->updateMember(100, ['first_name' => 'ByLink'], true);

        $this->assertSame('ByLink', $this->member(100)->first_name);
    }

    public function testSetMemberCheckedAndActive(): void
    {
        $members = $this->getTableLocator()->get('Members');
        $members->updateAll(['checked' => false, 'active' => false], ['id' => 100]);
        $management = $this->management(['MemberEditAll']);

        $this->assertTrue($management->setMemberChecked(100));
        $this->assertTrue($management->setMemberActive(100));

        $this->assertTrue($this->member(100)->checked);
        $this->assertTrue($this->member(100)->active);
    }

    public function testSetMemberCheckedRefused(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->management(['MemberViewAll'])->setMemberChecked(100);
    }

    public function testCancelMemberRegistration(): void
    {
        $this->addBill(10, 100);
        $this->addBill(11, 100, ['paid' => true]);
        $management = $this->management(['MemberEditAll']);

        $form = $management->cancelMemberRegistration(100);
        $this->assertSame(100, $form['member']->id);
        $this->assertSame([10], $form['openBills']->all()->extract('id')->toList());

        $this->assertTrue($management->cancelMemberRegistration(100, ['leaving_comment' => 'Moving']));
        $member = $this->member(100);
        $this->assertFalse($member->active);
        $this->assertFalse($member->registered);
        $this->assertSame('Moving', $member->leaving_comment);
        $this->assertEquals(\Cake\I18n\Date::today(), $member->date_fin);
    }

    public function testDeleteMember(): void
    {
        $this->assertTrue($this->management(['Admin'])->deleteMember(100));

        $this->assertFalse($this->getTableLocator()->get('Members')->exists(['id' => 100]));
    }

    public function testSetMemberLanguage(): void
    {
        $management = $this->management([]);

        $this->assertFalse($management->setMemberLanguage('xx'));
        $this->assertFalse($management->setMemberLanguage(null));
        $this->assertTrue($management->setMemberLanguage('de'));

        $this->assertSame('de', $this->member(1)->language);
        $this->assertSame('de', $this->Controller->getRequest()->getSession()->read('lng'));
    }

    public function testSetMemberSite(): void
    {
        $management = $this->management(['MemberViewAll']);

        $this->assertFalse($management->setMemberSite(9));
        $this->assertTrue($management->setMemberSite(1));
        $this->assertSame(1, $this->member(1)->pref_site_id);
        $this->assertTrue($management->setMemberSite(0));
        $this->assertSame(0, $this->member(1)->pref_site_id);
    }
}
