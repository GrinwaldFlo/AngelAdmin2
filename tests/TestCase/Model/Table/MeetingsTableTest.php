<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MeetingsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MeetingsTable Test Case
 */
class MeetingsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MeetingsTable
     */
    protected $Meetings;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Meetings',
        'app.Teams',
        'app.Presences',
        'app.Members',
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
        $config = TableRegistry::getTableLocator()->exists('Meetings') ? [] : ['className' => MeetingsTable::class];
        $this->Meetings = TableRegistry::getTableLocator()->get('Meetings', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Meetings);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Meetings->newEntity([
            'meeting_date' => '',
            'name' => '',
            'url' => str_repeat('a', 251),
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('meeting_date', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('url', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->Meetings->newEntity([
            'meeting_date' => '2025-01-15 09:00:00',
            'team_id' => 999999,
            'name' => 'Team meeting',
        ]);

        $this->assertFalse($this->Meetings->checkRules($entity));
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('team_id', $entity->getErrors());
    }

    /**
     * Test findMeetings method with the site filter
     *
     * @return void
     */
    public function testFindMeetingsSiteFilter(): void
    {
        $find = fn(int $siteId) => $this->Meetings->find('Meetings', teamId: 0, siteId: $siteId, meetingFilter: 0)
            ->contain('Teams')
            ->count();

        $this->assertSame(1, $find(0), 'All sites');
        $this->assertSame(1, $find(1), 'Site of the meeting team');
        $this->assertSame(0, $find(2), 'Other site');
    }
}
