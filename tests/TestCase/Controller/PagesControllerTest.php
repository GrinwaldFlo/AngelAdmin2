<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Home page of the logged member 1 (team 1). Slack is never called: the http client is mocked.
 *
 * @uses \App\Controller\PagesController
 */
class PagesControllerTest extends TestCase
{
    use HttpClientTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.Bills', 'app.Meetings', 'app.Presences',
    ];

    private const SLACK_CACHE = CACHE . 'slack_messages.json';

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        if (file_exists(self::SLACK_CACHE)) {
            rename(self::SLACK_CACHE, self::SLACK_CACHE . '.bak');
        }
        Configure::write('Slack.channelId', 'C1');
        $this->mockClientGet(
            'https://slack.com/api/conversations.history?channel=C1&limit=3',
            $this->newClientResponse(200, ['Content-Type: application/json'], json_encode(['ok' => true, 'messages' => []]))
        );
        foreach (['Bills', 'Meetings', 'Contents'] as $table) {
            $this->getTableLocator()->get($table)->deleteAll([]);
        }
    }

    protected function tearDown(): void
    {
        @unlink(self::SLACK_CACHE);
        if (file_exists(self::SLACK_CACHE . '.bak')) {
            rename(self::SLACK_CACHE . '.bak', self::SLACK_CACHE);
        }
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function addMeeting(int $id, string $name, string $date, array $fields = []): void
    {
        $values = $fields + ['id' => $id, 'meeting_date' => $date, 'team_id' => 1, 'name' => $name, 'big_event' => false, 'doodle' => false];
        $this->getTableLocator()->get('Meetings')->insertQuery()->insert(array_keys($values))->values($values)->execute();
    }

    public function testRequiresLogin(): void
    {
        $this->get('/');

        $this->assertRedirectContains('/users/login');
    }

    public function testHome(): void
    {
        $this->loginWith(['MemberViewAll']);

        $this->get('/');

        $this->assertResponseOk();
        $this->assertTemplate('home');
        $this->assertSame(1, $this->viewVariable('curMember')->id);
    }

    public function testHomeListsTheNextEventsOfTheTeams(): void
    {
        $this->addTeam(2, 'Other');
        $this->addMeeting(1, 'Training', (new DateTime('+2 days'))->format('Y-m-d H:i:s'));
        $this->addMeeting(2, 'Gala', (new DateTime('+5 days'))->format('Y-m-d H:i:s'), ['big_event' => true]);
        $this->addMeeting(3, 'Old training', (new DateTime('-2 days'))->format('Y-m-d H:i:s'));
        $this->addMeeting(4, 'Other team', (new DateTime('+2 days'))->format('Y-m-d H:i:s'), ['team_id' => 2]);
        $this->loginWith(['MemberViewAll']);

        $this->get('/');

        $this->assertSame(['Training'], $this->viewVariable('smallMeetings')->all()->extract('name')->toList());
        $this->assertSame(['Gala'], $this->viewVariable('bigMeetings')->all()->extract('name')->toList());
        $this->assertResponseContains('Training');
        $this->assertResponseNotContains('Other team');
    }

    public function testHomeListsOpenBillsOfTheMember(): void
    {
        $this->addMember(100, [1]);
        $this->addBill(10, 1, ['label' => 'Own fee']);
        $this->addBill(11, 1, ['label' => 'Paid fee', 'paid' => true]);
        $this->addBill(12, 100, ['label' => 'Other fee']);
        $this->loginWith(['MemberViewAll']);

        $this->get('/');

        $this->assertSame([10], $this->viewVariable('lateBills')->extract('id')->toList());
    }

    public function testHomeWithoutTeam(): void
    {
        $this->getTableLocator()->get('TeamsMembers')->deleteAll(['member_id' => 1]);
        $this->loginWith(['MemberViewAll']);

        $this->get('/');

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('smallMeetings'));
    }

    public function testDotPathsAreForbidden(): void
    {
        $this->loginWith([]);

        $this->get('/pages/..');
        $this->assertResponseCode(403);
        $this->get('/pages/home/.');
        $this->assertResponseCode(403);
    }

    public function testMissingPage(): void
    {
        $this->loginWith([]);

        $this->get('/pages/no-such-page');

        $this->assertResponseCode(404);
    }

    public function testPagesRootRedirects(): void
    {
        $this->loginWith([]);

        $this->get('/pages');

        $this->assertRedirect('/');
    }
}
