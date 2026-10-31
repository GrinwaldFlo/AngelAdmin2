<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AppController;
use App\Controller\BillsController;
use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * Methods shared by all the controllers. Member 1 coaches team 2 (see addCoachedTeams())
 *
 * @uses \App\Controller\AppController
 */
class AppControllerTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.Registrations', 'app.Data',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->loadRoutes();
        $this->getTableLocator()->get('Configurations')->deleteAll([]);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    private function setConfig(array $values): void
    {
        $query = $this->getTableLocator()->get('Configurations')->insertQuery()->insert(['label', 'value']);
        foreach ($values as $label => $value) {
            $query->values(['label' => $label, 'value' => $value]);
        }
        $query->execute();
    }

    private function addSite(int $id, string $city): void
    {
        $this->getTableLocator()->get('Sites')->insertQuery()
            ->insert(['id', 'city', 'feeMax', 'sender_email', 'sender'])
            ->values(['id' => $id, 'city' => $city, 'feeMax' => 1, 'sender_email' => "s$id@example.com", 'sender' => $city])
            ->execute();
    }

    // ---------------------------------------------------------------- initialize

    public function testConfigDefaults(): void
    {
        $config = $this->controller()->config;

        $this->assertSame((int)date('Y'), $config['year']);
        $this->assertSame('fr', $config['lng']);
        $this->assertSame('01.09', $config['firstDaySeason']);
        $this->assertSame(0, $config['showInfo']);
        $this->assertSame(0, $config['showCommMethod']);
        $this->assertSame(0, $config['showBvr']);
        $this->assertSame(0, $config['allowResiliation']);
        $this->assertArrayHasKey('dateEvent', $config);
        foreach (['fr', 'de', 'en', 'es', 'it'] as $lang) {
            $this->assertSame(1, $config[$lang]);
        }
        $this->assertEquals(new \DateTime('01.09.' . date('Y')), $config['dateSeasonStart']);
    }

    public function testConfigFromTable(): void
    {
        $this->setConfig(['year' => '2025', 'firstDaySeason' => '15.08', 'clubName' => 'My club', 'de' => '0']);

        $controller = $this->controller();

        $this->assertSame('My club', $controller->config['clubName']);
        $this->assertSame('0', $controller->config['de']);
        $this->assertEquals(new \DateTime('2025-08-15'), $controller->config['dateSeasonStart']);
        $this->assertSame($controller->config, $controller->viewBuilder()->getVar('config'));
    }

    public function testLoggedInSetsCurrentUserMemberAndRole(): void
    {
        $controller = $this->controller(AppController::class, $this->identity(['MemberViewAll']));

        $this->assertSame(1, $controller->curMember->id);
        $this->assertSame(1, $controller->curUser->member_id);
        $this->assertTrue($controller->curRole->MemberViewAll);
        $this->assertFalse($controller->curRole->Admin);
        $this->assertTrue($controller->viewBuilder()->getVar('isLogged'));
    }

    public function testLocaleOfTheLoggedMember(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'de'], ['id' => 1]);

        $this->controller(AppController::class, $this->identity([]));

        $this->assertSame('de', I18n::getLocale());
    }

    public function testLocaleFallsBackWhenLanguageDisabled(): void
    {
        $this->setConfig(['de' => '0', 'lng' => 'it']);
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'de'], ['id' => 1]);

        $this->controller(AppController::class, $this->identity([]));

        $this->assertSame('it', I18n::getLocale());
    }

    public function testLocaleOfTheSessionWhenNotLogged(): void
    {
        $controller = $this->controller();
        $this->assertSame('fr', I18n::getLocale());
        $this->assertFalse($controller->viewBuilder()->getVar('isLogged'));

        $controller = $this->controller();
        $controller->getRequest()->getSession()->write('lng', 'en');
        $this->controller(AppController::class, null, ['session' => $controller->getRequest()->getSession()]);
        $this->assertSame('en', I18n::getLocale());
    }

    // ---------------------------------------------------------------- hash access

    public function testHashFromUrl(): void
    {
        $hash = str_repeat('1', 40);

        $controller = $this->controller(AppController::class, null, ['params' => ['pass' => [$hash]]]);

        $this->assertTrue($controller->isHashAuthenticated());
        $this->assertSame(1, $controller->getHashAuthenticatedMemberData()->id);
        $cookie = $controller->getResponse()->getCookie('member_hash');
        $this->assertSame($hash, $cookie['value']);
        $this->assertTrue($cookie['httponly']);
    }

    public function testHashFromCookie(): void
    {
        $controller = $this->controller(AppController::class, null, ['cookies' => ['member_hash' => str_repeat('1', 40)]]);

        $this->assertTrue($controller->isHashAuthenticated());
        $this->assertSame(str_repeat('1', 40), $controller->getCurrentHash());
    }

    public function testInvalidOrUnknownHash(): void
    {
        foreach ([['params' => ['pass' => ['1']]], ['params' => ['pass' => [str_repeat('2', 40)]]], ['cookies' => ['member_hash' => str_repeat('2', 40)]]] as $request) {
            $controller = $this->controller(AppController::class, null, $request);
            $this->assertFalse($controller->isHashAuthenticated());
            $this->assertNull($controller->getHashAuthenticatedMemberData());
            $this->assertNull($controller->getResponse()->getCookie('member_hash'));
        }
    }

    public function testInvalidUrlHashFallsBackToCookie(): void
    {
        $controller = $this->controller(AppController::class, null, ['params' => ['pass' => ['12']], 'cookies' => ['member_hash' => str_repeat('1', 40)]]);

        $this->assertTrue($controller->isHashAuthenticated());
    }

    public function testClearHashCookie(): void
    {
        $controller = $this->controller();
        $controller->clearHashCookie();

        $cookie = $controller->getResponse()->getCookieCollection()->get('member_hash');
        $this->assertSame('', $cookie->getValue());
        $this->assertTrue($cookie->isExpired());
    }

    // ---------------------------------------------------------------- canAccessMember

    public function testCanAccessMember(): void
    {
        $this->addCoachedTeams();

        $controller = $this->controller(AppController::class, $this->identity([]));
        $this->assertTrue($controller->canAccessMember(1));
        $this->assertFalse($controller->canAccessMember(100));

        $controller = $this->controller(AppController::class, $this->identity(['MemberEditAll']));
        $this->assertTrue($controller->canAccessMember(101));

        $controller = $this->controller(AppController::class, $this->identity(['MemberEditAll', 'TeamScoped']));
        $this->assertTrue($controller->canAccessMember(100));
        $this->assertFalse($controller->canAccessMember(101));
    }

    public function testCanAccessMemberByHash(): void
    {
        $this->addMember(100);

        $controller = $this->controller(AppController::class, null, ['params' => ['pass' => [sha1('member100')]]]);
        $this->assertTrue($controller->canAccessMember(100));
        $this->assertFalse($controller->canAccessMember(1));

        $this->assertFalse($this->controller()->canAccessMember(1));
    }

    // ---------------------------------------------------------------- preferences

    public function testGetPrefSession(): void
    {
        $controller = $this->controller();
        $session = $controller->getRequest()->getSession();

        $this->assertSame(5, $controller->getPrefSession('teamId', null, 5));
        $this->assertSame(3, $controller->getPrefSession('teamId', 3, 5));
        $this->assertSame(3, $session->read('teamId'));
        $this->assertSame(3, $controller->getPrefSession('teamId', null, 5));
        $this->assertSame(3, $controller->getPrefSession('teamId', -999, 5));
        $this->assertSame(0, $controller->getPrefSession('teamId', 0, 5));
    }

    public function testGetPrefSessionStr(): void
    {
        $controller = $this->controller();

        $this->assertSame('fr', $controller->getPrefSessionStr('lng', null, 'fr'));
        $this->assertSame('de', $controller->getPrefSessionStr('lng', 'de', 'fr'));
        $this->assertSame('de', $controller->getPrefSessionStr('lng', null, 'fr'));
    }

    public function testGetSiteId(): void
    {
        $this->addSite(2, 'Second');
        $members = $this->getTableLocator()->get('Members');

        $controller = $this->controller(AppController::class, $this->identity([]));
        $this->assertSame(0, $controller->getSiteId());
        $this->assertSame(1, $controller->getSiteId(true));
        $this->assertSame(1, $controller->viewBuilder()->getVar('navSiteId'));

        $members->updateAll(['pref_site_id' => 2], ['id' => 1]);
        $this->assertSame(2, $this->controller(AppController::class, $this->identity([]))->getSiteId(true));

        // Deleted site
        $members->updateAll(['pref_site_id' => 9], ['id' => 1]);
        $this->assertSame(0, $this->controller(AppController::class, $this->identity([]))->getSiteId());
    }

    // ---------------------------------------------------------------- teams and members

    public function testGetTeamsActiv(): void
    {
        $this->addSite(2, 'Second');
        $this->addTeam(2, 'B team');
        $this->addTeam(3, 'A team', true, 2);
        $this->addTeam(4, 'Old', false);
        $this->getTableLocator()->get('Teams')->updateAll(['name' => 'C team'], ['id' => 1]);

        $controller = $this->controller(AppController::class, $this->identity(['MemberViewAll']));

        $this->assertSame([3 => 'A team', 2 => 'B team', 1 => 'C team'], $controller->getTeamsActiv());
        $this->assertSame([2 => 'B team', 1 => 'C team'], $controller->getTeamsActiv(1));
        $this->assertSame([3 => 'A team'], $controller->getTeamsActiv(2));
    }

    public function testGetTeamsActivOfCoach(): void
    {
        $this->addCoachedTeams();

        $controller = $this->controller(AppController::class, $this->identity(['MemberViewAll', 'TeamScoped']));
        $this->assertSame([2 => 'Juniors'], $controller->getTeamsActiv());
        $this->assertCount(3, $controller->getTeamsActiv(0, false));

        $controller = $this->controller(AppController::class, $this->identity(['MemberViewAll', 'TeamScoped'], 100));
        $this->assertSame([], $controller->getTeamsActiv());
    }

    public function testGetMembersOfCoach(): void
    {
        $this->addCoachedTeams();
        $ids = fn (AppController $controller) => $controller->getMembers(0, 0)->all()->extract('id')->toList();

        $all = $ids($this->controller(AppController::class, $this->identity(['MemberViewAll'])));
        sort($all);
        $this->assertSame([1, 100, 101, 102, 103], $all);

        $coached = $ids($this->controller(AppController::class, $this->identity(['MemberViewAll', 'TeamScoped'])));
        sort($coached);
        $this->assertSame([100, 102], $coached);
    }

    public function testMemberIsInSite(): void
    {
        $member = $this->getTableLocator()->get('Members')->get(1, contain: ['Teams']);
        $controller = $this->controller();

        $this->assertTrue($controller->memberIsInSite($member, 1));
        $this->assertFalse($controller->memberIsInSite($member, 2));
    }

    public function testMemberYoungerThan(): void
    {
        $members = $this->getTableLocator()->get('Members');
        $controller = $this->controller();
        $tenYears = $members->newEntity([]);
        $tenYears->date_birth = Date::today()->subYears(10)->subDays(1);
        $noBirthDate = $members->newEntity([]);

        $this->assertTrue($controller->memberYoungerThan($tenYears, 0));
        $this->assertTrue($controller->memberYoungerThan($tenYears, 11));
        $this->assertFalse($controller->memberYoungerThan($tenYears, 10));
        $this->assertTrue($controller->memberYoungerThan($noBirthDate, 0));
        $this->assertFalse($controller->memberYoungerThan($noBirthDate, 18));
    }

    public function testSaveSignatureCreatesThenUpdates(): void
    {
        $registrations = $this->getTableLocator()->get('Registrations');
        $registrations->deleteAll([]);
        $controller = $this->controller();

        $id = $controller->saveSignature(1, 'member1', 'parent1', 2030);
        $this->assertGreaterThan(0, $id);
        $this->assertSame($id, $controller->saveSignature(1, 'member2', 'parent2', 2030));
        $this->assertNotSame($id, $controller->saveSignature(1, 'member3', 'parent3', 2031));

        $signature = $registrations->get($id)->signature_member;
        $this->assertSame('member2', is_resource($signature) ? stream_get_contents($signature) : $signature);
        $this->assertSame(2, $registrations->find()->where(['member_id' => 1])->count());
    }

    public function testGetData(): void
    {
        $this->getTableLocator()->get('Data')->insertQuery()
            ->insert(['id', 'param', 'value', 'data_type'])
            ->values(['id' => 1, 'param' => 2, 'value' => 'two', 'data_type' => 'type'])
            ->values(['id' => 2, 'param' => 1, 'value' => 'one', 'data_type' => 'type'])
            ->values(['id' => 3, 'param' => 1, 'value' => 'other', 'data_type' => 'other'])
            ->execute();
        $controller = $this->controller();

        $this->assertSame(['one', 'two'], $controller->getData('type')->extract('value')->toList());
        $this->assertSame('two', $controller->getData('type', 2)->value);
        // Missing: an empty entity
        $this->assertNull($controller->getData('type', 9)->value);
    }

    // ---------------------------------------------------------------- bills

    public function testGetBills(): void
    {
        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->addSite(2, 'Second');
        $this->addMember(100, [1], ['active' => false]);
        $this->addBill(10, 1, ['due_date' => '2099-01-01', 'printed' => true]);
        $this->addBill(11, 1, ['due_date' => '2020-01-01', 'printed' => true]);
        $this->addBill(12, 1, ['paid' => true, 'printed' => true]);
        $this->addBill(13, 1, ['due_date' => '2099-01-01']);
        $this->addBill(14, 1, ['canceled' => true]);
        $this->addBill(15, 1, ['site_id' => 2]);
        $this->addBill(16, 100, ['due_date' => '2099-01-01']);
        $controller = $this->controller(BillsController::class, $this->identity(['BillViewAll']));
        $ids = function (int $billStatus, int $memberStatus, ?int $teamId = 0, int $siteId = 1) use ($controller): array {
            $ids = $controller->getBills($billStatus, $memberStatus, $teamId, $siteId)->all()->extract('id')->toList();
            sort($ids);

            return $ids;
        };

        $this->assertSame([10, 11, 12, 13, 16], $ids(0, 0));
        $this->assertSame([10, 11, 13, 16], $ids(1, 0));
        $this->assertSame([11], $ids(2, 0));
        $this->assertSame([12], $ids(3, 0));
        $this->assertSame([13, 16], $ids(4, 0));
        $this->assertSame([10, 11, 12, 13], $ids(0, 1));
        $this->assertSame([16], $ids(0, 2));
        $this->assertSame([15], $ids(0, 0, 0, 2));
        $this->assertSame([10, 11, 12, 13, 16], $ids(0, 0, 1));
        $this->assertSame([], $ids(0, 0, 9));
    }

    public function testGetBillsOfCoach(): void
    {
        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->addCoachedTeams();
        $this->addBill(10, 100);
        $this->addBill(11, 101);
        $this->addBill(12, 103);

        $controller = $this->controller(BillsController::class, $this->identity(['BillViewAll', 'TeamScoped']));

        $this->assertSame([10], $controller->getBills(0, 0, 0, 1)->all()->extract('id')->toList());
    }

    public function testGetLateBillsByMember(): void
    {
        if (ConnectionManager::get('test')->getDriver() instanceof \Cake\Database\Driver\Sqlite) {
            $this->markTestSkipped('GROUP_CONCAT ... SEPARATOR is MySQL only');
        }
        $this->getTableLocator()->get('Bills')->deleteAll([]);
        $this->addBill(10, 1, ['due_date' => '2020-01-01', 'amount' => 10]);
        $this->addBill(11, 1, ['due_date' => '2020-01-01', 'amount' => 15]);
        $this->addBill(12, 1, ['due_date' => '2099-01-01']);

        $rows = $this->controller(BillsController::class, $this->identity(['BillViewAll']))->getLateBillsByMember(0, 1)->all()->toList();

        $this->assertCount(1, $rows);
        $this->assertEquals(2, $rows[0]->count);
        $this->assertEquals(25, $rows[0]->total_amount);
    }

    // ---------------------------------------------------------------- urls

    public function testMemberUrl(): void
    {
        $hash = str_repeat('1', 40);
        $url = ['controller' => 'Members', 'action' => 'edit', 1];

        $this->assertStringEndsWith('/members/edit/1', $this->controller()->memberUrl($url, true));

        $controller = $this->controller(AppController::class, null, ['cookies' => ['member_hash' => $hash]]);
        $this->assertStringEndsWith('/members/edit/1', $controller->memberUrl($url));
        $this->assertStringEndsWith("/members/edit/1/$hash", $controller->memberUrl($url, true));
    }

    public function testRedirectMember(): void
    {
        $url = ['controller' => 'Members', 'action' => 'view', 1];

        $response = $this->controller(AppController::class, $this->identity([]))->redirectMember($url);
        $this->assertStringEndsWith('/members/view/1', $response->getHeaderLine('Location'));

        $controller = $this->controller(AppController::class, null, ['cookies' => ['member_hash' => str_repeat('1', 40)]]);
        $this->assertStringEndsWith('/members/my-page', $controller->redirectMember($url)->getHeaderLine('Location'));
    }
}
