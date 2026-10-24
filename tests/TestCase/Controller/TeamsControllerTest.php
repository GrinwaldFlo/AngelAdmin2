<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Controller\TeamsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\TeamsController Test Case
 *
 * @uses \App\Controller\TeamsController
 */
class TeamsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Teams',
        'app.Meetings',
        'app.Members',
        'app.Sites',
        'app.TeamsMembers',
        'app.Users',
        'app.Roles',
        'app.Configurations',
        'app.Contents',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->get('/teams');
        $this->assertResponseCode(302);
    }

    /**
     * Test index method filtered by the site chosen in the menu
     *
     * @return void
     */
    public function testIndexSiteFilter(): void
    {
        $this->getTableLocator()->get('Sites')->insertQuery()
            ->insert(['id', 'city', 'feeMax', 'sender_email', 'sender'])
            ->values(['id' => 2, 'city' => 'Second city', 'feeMax' => 1, 'sender_email' => 'second@example.com', 'sender' => 'Second'])
            ->execute();
        $this->getTableLocator()->get('Teams')->insertQuery()
            ->insert(['id', 'name', 'membership_fee', 'active', 'site_id'])
            ->values(['id' => 2, 'name' => 'Second team', 'membership_fee' => 0, 'active' => 1, 'site_id' => 2])
            ->execute();
        $members = $this->getTableLocator()->get('Members');
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)]);
        $teamIds = fn() => array_map(fn($team) => $team->id, $this->viewVariable('teams')->toArray());

        $this->get('/teams');
        $this->assertResponseOk();
        $this->assertEqualsCanonicalizing([1, 2], $teamIds());

        $members->updateAll(['pref_site_id' => 2], ['id' => 1]);
        $this->get('/teams');
        $this->assertResponseOk();
        $this->assertSame([2], $teamIds());
        $this->assertResponseContains('Second team');

        $members->updateAll(['pref_site_id' => 1], ['id' => 1]);
        $this->get('/teams');
        $this->assertSame([1], $teamIds());
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->get('/teams/view/1');
        $this->assertResponseCode(302);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $this->get('/teams/add');
        $this->assertResponseCode(302);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $this->get('/teams/edit/1');
        $this->assertResponseCode(302);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->post('/teams/delete/1');
        $this->assertResponseCode(302);
    }
}
