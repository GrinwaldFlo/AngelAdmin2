<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Entity\TeamsCoach;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Coach block of the attendance page and saving the presence type of a coach
 *
 * Team 1 (site 1) coached by member 1 (the logged user) and member 100 (help coach). Team 2 not coached by member 1.
 *
 * @uses \App\Controller\CoachPresencesController
 * @uses \App\View\Cell\CoachPresencesCell
 */
class CoachPresencesControllerTest extends TestCase
{
    use CoachPaymentDataTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    private int $main;
    private int $meetingId;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();

        $this->addTeam(2, 'Not coached');
        $this->addMember(100, [1]);
        $this->addCoach(1, 1);
        $this->addCoach(1, 100, TeamsCoach::HELP_COACH);
        $this->main = $this->addPresenceType(1, 'Main', 10);
        $this->addPresenceType(1, 'Secondary', 5);
        $this->addPresenceType(1, 'Old type', 1, false);
        $this->meetingId = $this->addMeeting(1);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function loginCoach(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditAll', 'MemberEditOwn', 'TeamScoped']);
    }

    private function typeOf(int $meetingId, int $memberId): ?int
    {
        return $this->getTableLocator()->get('CoachPresences')->find()->where(['meeting_id' => $meetingId, 'member_id' => $memberId])->first()?->coach_presence_type_id;
    }

    public function testAttendancePageShowsTheCoaches(): void
    {
        $this->loginCoach();
        $this->get('/meetings/view/' . $this->meetingId);

        $this->assertResponseOk();
        $this->assertResponseContains('class="cp-coaches"');
        $this->assertResponseContains('data-member="1"');
        $this->assertResponseContains('data-member="100"');
        $this->assertResponseContains('Help coach');
        $this->assertResponseContains('>Main</button>');
        $this->assertResponseContains('>Secondary</button>');
        $this->assertResponseNotContains('Old type');
        $this->assertResponseContains('coach-presences.js');
    }

    public function testAttendancePageShowsTheLimitsOfTheTeam(): void
    {
        $this->addPresenceLimit(1, $this->main, 1);
        $this->addPresenceLimit(2, $this->main, 3);
        $this->loginCoach();

        $this->get('/meetings/view/' . $this->meetingId);

        $this->assertResponseOk();
        $this->assertResponseContains('data-max="1"');
        $this->assertResponseNotContains('data-max="3"');
        $this->assertResponseContains('max 1 coach per meeting');
    }

    public function testRefusedWhenTheLimitIsReached(): void
    {
        $this->addPresenceLimit(1, $this->main, 1);
        $this->addCoachPresence($this->meetingId, 1, $this->main);
        $this->loginCoach();

        $this->post('/coach-presences/set-type/' . $this->meetingId, ['member_id' => 100, 'type_id' => $this->main]);

        $this->assertResponseCode(422);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertStringContainsString('maximum', $json['error']);
        $this->assertNull($this->typeOf($this->meetingId, 100));
    }

    public function testAttendancePageWithoutCoachHasNoBlock(): void
    {
        $this->loginWith();
        $this->get('/meetings/view/' . $this->addMeeting(2));

        $this->assertResponseOk();
        $this->assertResponseNotContains('cp-coaches');
    }

    public function testAttendancePageShowsPaidRowsLocked(): void
    {
        $this->addCoachPresence($this->meetingId, 1, $this->main);
        $this->getTableLocator()->get('CoachPayments')->payOpen(1, 1);
        $this->loginCoach();

        $this->get('/meetings/view/' . $this->meetingId);

        $this->assertResponseOk();
        $this->assertResponseContains('<span class="cp-type" aria-pressed="true">Main</span>');
        $this->assertResponseContains('Paid');
    }

    public function testCoachSetsThePresenceOfAnotherCoach(): void
    {
        $this->loginCoach();
        $this->post('/coach-presences/set-type/' . $this->meetingId, ['member_id' => 100, 'type_id' => $this->main]);

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertSame(['saved' => true, 'error' => null], json_decode((string)$this->_response->getBody(), true));
        $this->assertSame($this->main, $this->typeOf($this->meetingId, 100));
    }

    public function testEmptyTypeClearsThePresence(): void
    {
        $this->addCoachPresence($this->meetingId, 1, $this->main);
        $this->loginCoach();

        $this->post('/coach-presences/set-type/' . $this->meetingId, ['member_id' => 1, 'type_id' => '']);

        $this->assertResponseOk();
        $this->assertNull($this->typeOf($this->meetingId, 1));
    }

    public function testRefusedWithAnErrorMessage(): void
    {
        $this->loginCoach();
        $this->post('/coach-presences/set-type/' . $this->meetingId, ['member_id' => 1, 'type_id' => 999]);

        $this->assertResponseCode(422);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($json['saved']);
        $this->assertNotEmpty($json['error']);
    }

    public function testInvalidData(): void
    {
        $this->loginCoach();
        $this->post('/coach-presences/set-type/' . $this->meetingId, ['member_id' => 'abc', 'type_id' => $this->main]);
        $this->assertResponseCode(400);

        $this->post('/coach-presences/set-type/' . $this->meetingId, ['member_id' => 1, 'type_id' => 'abc']);
        $this->assertResponseCode(400);
    }

    public function testForbiddenForATeamNotCoached(): void
    {
        $this->addCoach(2, 100);
        $meetingId = $this->addMeeting(2);
        $this->loginCoach();

        $this->post('/coach-presences/set-type/' . $meetingId, ['member_id' => 100, 'type_id' => $this->main]);

        $this->assertResponseCode(403);
        $this->assertNull($this->typeOf($meetingId, 100));
    }

    public function testRequiresLogin(): void
    {
        $this->post('/coach-presences/set-type/' . $this->meetingId, ['member_id' => 1, 'type_id' => $this->main]);

        $this->assertRedirectContains('/users/login');
        $this->assertNull($this->typeOf($this->meetingId, 1));
    }

    public function testHashAccessCanNotSet(): void
    {
        $this->cookie('member_hash', sha1('member100'));
        $this->post('/coach-presences/set-type/' . $this->meetingId, ['member_id' => 100, 'type_id' => $this->main]);

        $this->assertRedirectContains('/users/login');
        $this->assertNull($this->typeOf($this->meetingId, 100));
    }

    public function testRejectsGet(): void
    {
        $this->loginCoach();
        $this->get('/coach-presences/set-type/' . $this->meetingId);

        $this->assertResponseCode(405);
    }
}
