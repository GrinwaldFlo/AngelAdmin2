<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\MembersController;
use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Member 1 coaches team 2 (members 100, 102), not team 3 (member 101), member 103 is inactive in team 2
 *
 * @uses \App\Controller\Component\MemberListComponent
 */
class MemberListComponentTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Fields', 'app.FieldTypes',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->addCoachedTeams();
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function controllerWith(array $rights): MembersController
    {
        return $this->controller(MembersController::class, $this->identity($rights));
    }

    private function pref(int $teamId = 0, int $memberFilter = 1, int $siteId = 0): PrefResult
    {
        $pref = (new \ReflectionClass(PrefResult::class))->newInstanceWithoutConstructor();
        $pref->teamId = $teamId;
        $pref->memberFilter = $memberFilter;
        $pref->siteId = $siteId;

        return $pref;
    }

    private function ids(iterable $members): array
    {
        $ids = [];
        foreach ($members as $member) {
            $ids[] = $member->id;
        }
        sort($ids);

        return $ids;
    }

    public function testGetMembersList(): void
    {
        $list = $this->controllerWith(['MemberViewAll'])->MemberList;

        $this->assertSame([1, 100, 101, 102], $this->ids($list->getMembersList($this->pref())));
        $this->assertSame([100, 102], $this->ids($list->getMembersList($this->pref(2))));
        $this->assertSame([103], $this->ids($list->getMembersList($this->pref(0, 2))));
    }

    public function testListsOfCoachAreScoped(): void
    {
        $list = $this->controllerWith(['MemberViewAll', 'TeamScoped'])->MemberList;

        $this->assertSame([100, 102], $this->ids($list->getMembersList($this->pref())));
        $this->assertSame([100, 102], $this->ids($list->getEmailList(0, 0)));
        $this->assertSame([100, 102], $this->ids($list->getPicturesList(0, 0)));
        $this->assertSame([100, 102], $this->ids($list->getFamilyReductionList()));
    }

    public function testGetNewMembersList(): void
    {
        $this->addMember(104, []);
        $this->addMember(105, [], ['active' => false]);

        $this->assertSame([104], $this->ids($this->controllerWith(['MemberViewAll'])->MemberList->getNewMembersList()));
    }

    public function testGetBatchRegisterList(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['registered' => 1], []);
        $this->getTableLocator()->get('Members')->updateAll(['registered' => 0], ['id IN' => [100, 101]]);
        $pref = $this->pref(2);

        $members = $this->controllerWith(['MemberEditAll'])->MemberList->getBatchRegisterList($pref);

        $this->assertSame(4, $pref->memberFilter);
        $this->assertSame([100], $this->ids($members));
    }

    public function testGetFamilyReductionListSortedByAddress(): void
    {
        $members = $this->getTableLocator()->get('Members');
        $members->updateAll(['city' => 'Renens', 'address' => 'Rue du Lac 4'], ['id' => 100]);
        $members->updateAll(['city' => 'Lausanne', 'address' => 'Av. de la Gare 2'], ['id' => 101]);
        $members->updateAll(['city' => 'Renens', 'address' => 'Chemin du Lac, 4'], ['id' => 102]);
        $members->updateAll(['city' => 'Morges', 'address' => 'Rte de Lausanne 1'], ['id' => 1]);

        $list = $this->controllerWith(['MemberViewAll'])->MemberList->getFamilyReductionList();

        // Same address written differently: next to each other
        $this->assertSame(['Lausanne, gare 2', 'Morges, lausanne 1', 'Renens, lac 4', 'Renens, lac 4'], array_map(fn ($member) => $member['FullAddress'], $list));
    }

    public function testGetBirthdaysList(): void
    {
        if (ConnectionManager::get('test')->getDriver() instanceof \Cake\Database\Driver\Sqlite) {
            $this->markTestSkipped('DAYOFYEAR() is MySQL only');
        }
        $members = $this->getTableLocator()->get('Members');
        $members->updateAll(['date_birth' => '2010-12-01'], ['id' => 100]);
        $members->updateAll(['date_birth' => '2015-02-01'], ['id' => 102]);

        $this->assertSame([102, 100], $this->controllerWith(['MemberViewAll'])->MemberList->getBirthdaysList(2, 1)->all()->extract('id')->toList());
    }
}
