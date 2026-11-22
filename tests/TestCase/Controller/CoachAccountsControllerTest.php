<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Coach balance and IBAN on My account (Members/view of the own member)
 *
 * Member 1 (logged user) coaches team 1 (site 1). Site 2 with team 5 is not coached by member 1.
 *
 * @uses \App\Controller\CoachAccountsController
 * @uses \App\View\Cell\CoachBalanceCell
 * @uses \App\Policy\CoachAccountPolicy
 */
class CoachAccountsControllerTest extends TestCase
{
    use CoachPaymentDataTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $this->addSite(2);
        $this->addTeam(5, 'Other city', true, 2);
        $this->addMember(100, [1]);
        $this->addUser(2, 100);
        $this->addCoach(1, 1);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function iban(int $memberId, int $siteId): ?string
    {
        return $this->getTableLocator()->get('CoachAccounts')->accountOf($memberId, $siteId)?->iban;
    }

    public function testMyAccountShowsNoIbanWarning(): void
    {
        $this->loginWith();
        $this->get('/members/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('id="coach-balance"');
        $this->assertResponseContains('No IBAN set');
        $this->assertResponseContains('Set IBAN');
    }

    public function testMyAccountShowsTheMaskedIbanOnly(): void
    {
        $this->addCoachAccount(1, 1);
        $this->loginWith();
        $this->get('/members/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('•••• 2957');
        $this->assertResponseContains('Change IBAN');
        $this->assertResponseNotContains('No IBAN set');
        $this->assertResponseNotContains('762011623852');
    }

    public function testMyAccountShowsTheBalance(): void
    {
        $main = $this->addPresenceType(1, 'Main', 10);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $main);
        $this->addCoachPresence($this->addMeeting(1, '-1 days'), 1, $main);
        $this->addCoachPresence($this->addMeeting(1, '+2 days'), 1, $main);
        $this->loginWith();

        $this->get('/members/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('CHF');
        $this->assertResponseContains('20.00');
        $this->assertResponseContains('1 meeting');
    }

    public function testNotShownOnThePageOfAnotherMember(): void
    {
        $this->addCoach(1, 100);
        $this->loginWith();
        $this->get('/members/view/100');

        $this->assertResponseOk();
        $this->assertResponseNotContains('coach-balance');
    }

    public function testNotShownToAMemberWhoIsNotCoach(): void
    {
        $this->loginWith(null);
        $this->getTableLocator()->get('TeamsCoaches')->deleteAll([]);
        $this->get('/members/view/1');

        $this->assertResponseOk();
        $this->assertResponseNotContains('coach-balance');
    }

    public function testCoachSetsTheirIban(): void
    {
        $this->loginWith();
        $this->post('/coach-accounts/set-iban/1', ['iban' => 'ch93 0076 2011 6238 5295 7']);

        $this->assertRedirectContains('/members/view/1');
        $this->assertSame(self::IBAN, $this->iban(1, 1));
        $this->assertFlashElement('flash/success');
    }

    public function testCoachReplacesTheirIban(): void
    {
        $this->addCoachAccount(1, 1);
        $this->loginWith();
        $this->post('/coach-accounts/set-iban/1', ['iban' => 'LI21088100002324013AA']);

        $this->assertSame('LI21088100002324013AA', $this->iban(1, 1));
    }

    public function testInvalidIbanIsRefused(): void
    {
        $this->addCoachAccount(1, 1);
        $this->loginWith();
        $this->post('/coach-accounts/set-iban/1', ['iban' => 'DE89370400440532013000']);

        $this->assertRedirectContains('/members/view/1');
        $this->assertFlashElement('flash/error');
        $this->assertSame(self::IBAN, $this->iban(1, 1));
    }

    public function testMemberIdInTheDataIsIgnored(): void
    {
        $this->addCoach(1, 100);
        $this->loginWith();
        $this->post('/coach-accounts/set-iban/1', ['iban' => self::IBAN, 'member_id' => 100]);

        $this->assertSame(self::IBAN, $this->iban(1, 1));
        $this->assertNull($this->iban(100, 1));
    }

    public function testRefusedForASiteWhereNotCoach(): void
    {
        $this->loginWith();
        $this->post('/coach-accounts/set-iban/2', ['iban' => self::IBAN]);

        $this->assertResponseCode(403);
        $this->assertNull($this->iban(1, 2));
    }

    public function testRefusedForAMemberWhoIsNotCoach(): void
    {
        $this->sessionAs(2);
        $this->post('/coach-accounts/set-iban/1', ['iban' => self::IBAN]);

        $this->assertResponseCode(403);
        $this->assertNull($this->iban(100, 1));
    }

    public function testRequiresLogin(): void
    {
        $this->post('/coach-accounts/set-iban/1', ['iban' => self::IBAN]);

        $this->assertRedirectContains('/users/login');
        $this->assertNull($this->iban(1, 1));
    }

    public function testHashAccessCanNotSetAnIban(): void
    {
        $this->cookie('member_hash', $this->getTableLocator()->get('Members')->get(1)->hash);
        $this->post('/coach-accounts/set-iban/1', ['iban' => self::IBAN]);

        $this->assertRedirectContains('/users/login');
        $this->assertNull($this->iban(1, 1));
    }
}
