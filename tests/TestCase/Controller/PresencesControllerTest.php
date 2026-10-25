<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Entity\Presence;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\PresencesController Test Case
 *
 * @uses \App\Controller\PresencesController
 */
class PresencesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Presences',
        'app.Meetings',
        'app.Members',
        'app.Teams',
        'app.TeamsMembers',
        'app.Sites',
        'app.Users',
        'app.Roles',
        'app.Configurations',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
    }

    private function login(): void
    {
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)]);
    }

    private function state(int $meetingId, int $memberId): ?int
    {
        $presence = $this->getTableLocator()->get('Presences')->find()->where(['meeting_id' => $meetingId, 'member_id' => $memberId])->first();
        return $presence?->state;
    }

    public function testSetPresenceRequiresLogin(): void
    {
        $this->post('/presences/set-presence/1', ['member_id' => 1, 'state' => Presence::ABSENT]);

        $this->assertRedirectContains('/users/login');
        $this->assertSame(Presence::PRESENT, $this->state(1, 1));
    }

    public function testSetPresenceRejectsGet(): void
    {
        $this->login();
        $this->get('/presences/set-presence/1/1/0');

        $this->assertResponseCode(405);
        $this->assertSame(Presence::PRESENT, $this->state(1, 1));
    }

    public function testSetPresenceForbiddenWithoutViewRight(): void
    {
        $this->getTableLocator()->get('Roles')->updateAll(['MemberViewAll' => 0], ['id' => 1]);
        $this->login();
        $this->post('/presences/set-presence/1', ['member_id' => 1, 'state' => Presence::ABSENT]);

        $this->assertResponseCode(403);
        $this->assertSame(Presence::PRESENT, $this->state(1, 1));
    }

    public function testSetPresenceUpdatesState(): void
    {
        $this->login();
        $this->post('/presences/set-presence/1', ['member_id' => 1, 'state' => Presence::LATE]);

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['saved']);
        $this->assertSame([Presence::PRESENT => 0, Presence::ABSENT => 0, Presence::EXCUSED => 0, Presence::LATE => 1], $json['counts']);
        $this->assertSame(Presence::LATE, $this->state(1, 1));
        $this->assertSame(1, $this->getTableLocator()->get('Presences')->find()->count());
        $this->assertSame(1, $this->getTableLocator()->get('Meetings')->get(1)->late);
    }

    public function testSetPresenceCreatesSeveral(): void
    {
        $members = $this->getTableLocator()->get('Members');
        $copy = $members->newEntity($members->get(1)->toArray(), ['validate' => false]);
        $copy->id = 2;
        $copy->first_name = 'Second';
        $copy->hash = str_repeat('2', 40);
        $members->saveOrFail($copy, ['checkRules' => false]);
        $meetings = $this->getTableLocator()->get('Meetings');
        $meeting = $meetings->saveOrFail($meetings->newEntity(['meeting_date' => '2030-01-01 18:00', 'name' => 'New', 'team_id' => 1]));

        $this->login();
        $this->post('/presences/set-presence/' . $meeting->id, ['member_id' => [1, 2], 'state' => Presence::PRESENT]);

        $this->assertResponseOk();
        $this->assertSame(Presence::PRESENT, $this->state($meeting->id, 1));
        $this->assertSame(Presence::PRESENT, $this->state($meeting->id, 2));
        $this->assertSame(2, $meetings->get($meeting->id)->present);
    }

    public function testSetPresenceRejectsInvalidState(): void
    {
        $this->login();
        $this->post('/presences/set-presence/1', ['member_id' => 1, 'state' => 7]);

        $this->assertResponseCode(400);
        $this->assertSame(Presence::PRESENT, $this->state(1, 1));
    }

    public function testSetPresenceRejectsUnknownMember(): void
    {
        $this->login();
        $this->post('/presences/set-presence/1', ['member_id' => 99, 'state' => Presence::ABSENT]);

        $this->assertResponseCode(422);
        $this->assertNull($this->state(1, 99));
    }

    public function testSetPresenceUnknownMeeting(): void
    {
        $this->login();
        $this->post('/presences/set-presence/99', ['member_id' => 1, 'state' => Presence::ABSENT]);

        $this->assertResponseCode(404);
    }
}
