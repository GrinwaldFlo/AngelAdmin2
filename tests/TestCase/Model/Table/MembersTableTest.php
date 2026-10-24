<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MembersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MembersTable Test Case
 */
class MembersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MembersTable
     */
    protected $Members;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Members',
        'app.Bills',
        'app.Presences',
        'app.Meetings',
        'app.Users',
        'app.Teams',
        'app.TeamsMembers',
        'app.Sites',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Members') ? [] : ['className' => MembersTable::class];
        $this->Members = TableRegistry::getTableLocator()->get('Members', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Members);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Members->newEntity([
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalid-email',
            'multi_payment' => 9,
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('multi_payment', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->markTestSkipped('MembersTable::buildRules currently returns rules unchanged. Add assertions when integrity rules are introduced.');
    }

    /**
     * Test dashboard counters with the site filter
     *
     * @return void
     */
    public function testCountersSiteFilter(): void
    {
        foreach ([0, 1] as $siteId) {
            $this->assertSame(1, $this->Members->CountActiveMembers($siteId));
            $this->assertSame(0, $this->Members->CountNotRegistered($siteId));
            $this->assertSame(1, $this->Members->CountActiveChilds($siteId));
            $this->assertSame(0, $this->Members->CountActiveAdults($siteId));
            $this->assertSame(1, $this->Members->CountActiveFeesPaid($siteId));
        }

        $this->assertSame(0, $this->Members->CountActiveMembers(2));
        $this->assertSame(0, $this->Members->CountNotRegistered(2));
        $this->assertSame(0, $this->Members->CountActiveChilds(2) + $this->Members->CountActiveAdults(2));
        $this->assertSame(0, $this->Members->CountActiveFeesPaid(2));
    }

    /**
     * Children / adults split at 18 years old
     *
     * @return void
     */
    public function testCountActiveChildsAdults(): void
    {
        $eighteenToday = (new \DateTime('today'))->sub(new \DateInterval('P18Y'));

        $this->Members->updateAll(['date_birth' => $eighteenToday->format('Y-m-d')], ['id' => 1]);
        $this->assertSame(0, $this->Members->CountActiveChilds(), '18 today is adult');
        $this->assertSame(1, $this->Members->CountActiveAdults(), '18 today is adult');

        $this->Members->updateAll(['date_birth' => $eighteenToday->modify('+1 day')->format('Y-m-d')], ['id' => 1]);
        $this->assertSame(1, $this->Members->CountActiveChilds(), '18 tomorrow is child');
        $this->assertSame(0, $this->Members->CountActiveAdults(), '18 tomorrow is child');
    }

    /**
     * A member in two teams of the same site is counted once
     *
     * @return void
     */
    public function testCountActiveMembersInTwoTeamsOfSite(): void
    {
        $this->getTableLocator()->get('Teams')->insertQuery()
            ->insert(['id', 'name', 'membership_fee', 'active', 'site_id'])
            ->values(['id' => 2, 'name' => 'Second team', 'membership_fee' => 0, 'active' => 1, 'site_id' => 1])
            ->execute();
        $this->getTableLocator()->get('TeamsMembers')->insertQuery()
            ->insert(['team_id', 'member_id'])
            ->values(['team_id' => 2, 'member_id' => 1])
            ->execute();

        $this->assertSame(1, $this->Members->CountActiveMembers(1));
    }

    /**
     * Test queryInSite method for alumni
     *
     * @return void
     */
    public function testQueryInSiteAlumni(): void
    {
        $this->assertSame(0, $this->Members->queryInSite(1, false)->count());

        $this->Members->updateAll(['active' => 0], ['id' => 1]);
        $this->assertSame(1, $this->Members->queryInSite(1, false)->count());
        $this->assertSame(0, $this->Members->queryInSite(1)->count());
    }

    /**
     * Test CountActiveWithoutTeam method
     *
     * @return void
     */
    public function testCountActiveWithoutTeam(): void
    {
        $this->assertSame(0, $this->Members->CountActiveWithoutTeam());

        $this->getTableLocator()->get('TeamsMembers')->deleteAll(['member_id' => 1]);
        $this->assertSame(1, $this->Members->CountActiveWithoutTeam());
    }
}
