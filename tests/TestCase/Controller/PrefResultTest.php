<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AppController;
use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Sites 1 and 2. Teams 1 (site 1), 2 (site 1), 3 (site 2)
 *
 * @uses \App\Controller\PrefResult
 */
class PrefResultTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->getTableLocator()->get('Sites')->insertQuery()
            ->insert(['id', 'city', 'feeMax', 'sender_email', 'sender'])
            ->values(['id' => 2, 'city' => 'Second', 'feeMax' => 1, 'sender_email' => 's2@example.com', 'sender' => 'Second'])
            ->execute();
        $this->addTeam(2, 'Juniors');
        $this->addTeam(3, 'Seniors', true, 2);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function app(array $rights = ['MemberViewAll']): AppController
    {
        return $this->controller(AppController::class, $this->identity($rights));
    }

    public function testGetSitesIsLoadedOnce(): void
    {
        $this->assertSame([1, 2], array_keys(PrefResult::getSites()));

        $this->getTableLocator()->get('Sites')->updateAll(['city' => 'Renamed'], ['id' => 2]);
        $this->assertSame('Second', PrefResult::getSites()[2]->city);

        PrefResult::$sites = null;
        $this->assertSame('Renamed', PrefResult::getSites()[2]->city);
    }

    public function testGetSiteAndValidSiteId(): void
    {
        $this->assertSame('Second', PrefResult::getSite(2)->city);
        $this->assertNull(PrefResult::getSite(9));
        $this->assertSame(2, PrefResult::validSiteId(2));
        $this->assertSame(0, PrefResult::validSiteId(9));
        $this->assertSame(0, PrefResult::validSiteId(null));
    }

    public function testValidateTeamId(): void
    {
        $teams = [2 => 'Juniors', 3 => 'Seniors'];

        $this->assertSame(2, PrefResult::validateTeamId(2, $teams));
        $this->assertSame(0, PrefResult::validateTeamId(1, $teams));
        $this->assertSame(0, PrefResult::validateTeamId(0, $teams));
        $this->assertSame(0, PrefResult::validateTeamId(null, $teams));
        $this->assertSame(0, PrefResult::validateTeamId(2, []));
    }

    public function testDefaultsAllSites(): void
    {
        $pref = new PrefResult($this->app());

        $this->assertSame(0, $pref->siteId);
        $this->assertNull($pref->site);
        $this->assertSame(0, $pref->teamId);
        $this->assertSame([2, 1, 3], array_keys($pref->teams));
        $this->assertSame(1, $pref->memberFilter);
    }

    public function testSiteOfTheMember(): void
    {
        $this->getTableLocator()->get('Members')->updateAll(['pref_site_id' => 2], ['id' => 1]);

        $pref = new PrefResult($this->app(), 3, 2);

        $this->assertSame(2, $pref->siteId);
        $this->assertSame('Second', $pref->site->city);
        $this->assertSame([3 => 'Seniors'], $pref->teams);
        $this->assertSame(3, $pref->teamId);
        $this->assertSame(2, $pref->memberFilter);
    }

    public function testSiteRequired(): void
    {
        $pref = new PrefResult($this->app(), null, null, true);

        $this->assertSame(1, $pref->siteId);
        $this->assertSame([2, 1], array_keys($pref->teams));
    }

    public function testTeamOfAnotherSiteIsReset(): void
    {
        $app = $this->app();

        $pref = new PrefResult($app, 3, null, true);

        $this->assertSame(0, $pref->teamId);
    }

    public function testTeamAndFilterKeptInSession(): void
    {
        $app = $this->app();
        new PrefResult($app, 2, 0);

        $pref = new PrefResult($app, -999, null);

        $this->assertSame(2, $pref->teamId);
        $this->assertSame(0, $pref->memberFilter);
    }

    public function testCoachOnlySeesItsTeams(): void
    {
        $this->addCoach(2, 1);

        $pref = new PrefResult($this->app(['MemberViewAll', 'TeamScoped']), 3);

        $this->assertSame([2 => 'Juniors'], $pref->teams);
        $this->assertSame(0, $pref->teamId);
    }
}
