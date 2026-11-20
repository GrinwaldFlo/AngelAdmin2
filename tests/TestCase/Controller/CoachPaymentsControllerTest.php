<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\I18n\Number;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Admin pages of the coach payment module: settings, reporting, payments
 *
 * Site 1: team 1 coached by member 100. Site 2: team 5 coached by member 101.
 * The logged user is member 1, an admin, the site of the pages is site 1 (its pref_site_id).
 *
 * @uses \App\Controller\CoachPaymentsController
 * @uses \App\Policy\CoachPaymentPolicy
 */
class CoachPaymentsControllerTest extends TestCase
{
    use CoachPaymentDataTrait;
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    private int $main;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $this->addSite(2, 'Second city');
        $this->addTeam(5, 'Other city', true, 2);
        $this->addMember(100, [1]);
        $this->addMember(101, [5]);
        $this->addCoach(1, 100);
        $this->addCoach(5, 101);
        $this->setAddress(100);
        $this->setSiteAddress();
        $this->main = $this->addPresenceType(1, 'Main', 10);
        $this->getTableLocator()->get('Members')->updateAll(['pref_site_id' => 1], ['id' => 1]);
    }

    protected function tearDown(): void
    {
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function loginAdmin(): void
    {
        $this->loginWith(['Admin', 'MemberViewAll', 'MemberEditAll']);
    }

    private function openCount(int $memberId): int
    {
        return $this->getTableLocator()->get('CoachPresences')->find()->where(['member_id' => $memberId, 'coach_payment_id IS' => null])->count();
    }

    public function testPagesAreForAdminsOnly(): void
    {
        $this->loginWith(['MemberViewAll', 'MemberEditAll', 'BillViewAll', 'BillEditAll']);

        foreach (['/coach-payments', '/coach-payments/settings'] as $url) {
            $this->get($url);
            $this->assertResponseCode(403, $url);
        }
        $this->post('/coach-payments/pay/100');
        $this->assertResponseCode(403);
        $this->post('/coach-payments/set-iban/100', ['iban' => self::IBAN]);
        $this->assertResponseCode(403);
        $this->assertNull($this->getTableLocator()->get('CoachAccounts')->accountOf(100, 1));
    }

    public function testSettingsListTheTypesAndCoachesOfTheSite(): void
    {
        $this->addPresenceType(2, 'Type of site 2', 3);
        $this->addCoachAccount(100, 1);
        $this->loginAdmin();

        $this->get('/coach-payments/settings');

        $this->assertResponseOk();
        $this->assertResponseContains('Main');
        $this->assertResponseNotContains('Type of site 2');
        $this->assertResponseContains('First100 Last100');
        $this->assertResponseNotContains('First101 Last101');
        $this->assertResponseContains('•••• 2957');
        $this->assertResponseNotContains('762011623852');
    }

    public function testAddEditAndDeleteType(): void
    {
        $this->loginAdmin();
        $types = $this->getTableLocator()->get('CoachPresenceTypes');

        $this->post('/coach-payments/add-type', ['label' => 'Secondary', 'amount' => '5', 'sort' => 2, 'site_id' => 2]);
        $this->assertRedirectContains('/coach-payments/settings');
        $type = $types->find()->where(['label' => 'Secondary'])->firstOrFail();
        $this->assertSame(1, $type->site_id);

        $this->post('/coach-payments/edit-type/' . $type->id, ['label' => 'Second', 'amount' => '6', 'sort' => 2, 'active' => 0]);
        $this->assertRedirectContains('/coach-payments/settings');
        $type = $types->get($type->id);
        $this->assertSame('Second', $type->label);
        $this->assertSame(6.0, (float)$type->amount);
        $this->assertFalse($type->active);

        $this->post('/coach-payments/delete-type/' . $type->id);
        $this->assertFalse($types->exists(['id' => $type->id]));
    }

    public function testInvalidTypeIsRefused(): void
    {
        $this->loginAdmin();
        $this->post('/coach-payments/add-type', ['label' => '', 'amount' => '-1']);

        $this->assertFlashElement('flash/error');
        $this->assertSame(1, $this->getTableLocator()->get('CoachPresenceTypes')->find()->count());
    }

    public function testUsedTypeIsNotDeleted(): void
    {
        $this->addCoachPresence($this->addMeeting(1), 100, $this->main);
        $this->loginAdmin();

        $this->post('/coach-payments/delete-type/' . $this->main);

        $this->assertFlashElement('flash/error');
        $this->assertTrue($this->getTableLocator()->get('CoachPresenceTypes')->exists(['id' => $this->main]));
    }

    public function testTypeOfAnotherSiteCanNotBeEditedNorDeleted(): void
    {
        $other = $this->addPresenceType(2, 'Other', 7);
        $this->loginAdmin();

        $this->post('/coach-payments/edit-type/' . $other, ['label' => 'Changed', 'amount' => 1]);
        $this->assertSame('Other', $this->getTableLocator()->get('CoachPresenceTypes')->get($other)->label);

        $this->post('/coach-payments/delete-type/' . $other);
        $this->assertTrue($this->getTableLocator()->get('CoachPresenceTypes')->exists(['id' => $other]));
    }

    public function testAdminSetsAndRemovesTheIbanOfACoach(): void
    {
        $this->loginAdmin();
        $accounts = $this->getTableLocator()->get('CoachAccounts');

        $this->post('/coach-payments/set-iban/100', ['iban' => self::IBAN]);
        $this->assertSame(self::IBAN, $accounts->accountOf(100, 1)?->iban);

        $this->post('/coach-payments/remove-iban/100');
        $this->assertNull($accounts->accountOf(100, 1));
    }

    public function testAdminCanNotSetTheIbanOfACoachOfAnotherSite(): void
    {
        $this->loginAdmin();
        $this->post('/coach-payments/set-iban/101', ['iban' => self::IBAN]);

        $this->assertFlashElement('flash/error');
        $this->assertSame(0, $this->getTableLocator()->get('CoachAccounts')->find()->count());
    }

    public function testReportShowsTheBalanceAndTheQrCode(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed to create the QR code');
        }
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 100, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-1 days'), 100, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '+2 days'), 100, $this->main);
        $this->addCoachAccount(100, 1);
        $this->loginAdmin();

        $this->get('/coach-payments');

        $this->assertResponseOk();
        $this->assertResponseContains('id="coach-100"');
        $this->assertResponseContains('src="data:image/svg+xml');
        $this->assertResponseContains('Mark ' . Number::currency(20, 'CHF') . ' as paid');
        $this->assertResponseContains('name="amount" value="20.00"');
        $this->assertResponseContains('Planned');
        $this->assertResponseNotContains('First101 Last101');
        $this->assertResponseNotContains('762011623852');
    }

    public function testReportWithoutIbanExplainsWhy(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 100, $this->main);
        $this->loginAdmin();

        $this->get('/coach-payments');

        $this->assertResponseOk();
        $this->assertResponseContains('No IBAN set');
        $this->assertResponseNotContains('data:image/svg+xml');
        $this->assertResponseNotContains('/coach-payments/pay/100');
    }

    public function testPayMarksTheDuePresencesAsPaid(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 100, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '+2 days'), 100, $this->main);
        $this->loginAdmin();

        $this->post('/coach-payments/pay/100', ['amount' => '10.00']);

        $this->assertRedirectContains('/coach-payments#coach-100');
        $this->assertFlashElement('flash/success');
        $this->assertSame(1, $this->getTableLocator()->get('CoachPayments')->find()->count());
        $this->assertSame(1, $this->openCount(100));
        $this->assertSame(0.0, $this->getTableLocator()->get('CoachPresences')->balances(1)[100]['dueTotal']);
    }

    public function testPayRefusedWhenTheAmountChanged(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 100, $this->main);
        $this->loginAdmin();

        $this->post('/coach-payments/pay/100', ['amount' => '5.00']);

        $this->assertFlashElement('flash/error');
        $this->assertSame(0, $this->getTableLocator()->get('CoachPayments')->find()->count());
    }

    public function testPayOnlyTheSelectedSite(): void
    {
        $this->addCoachPresence($this->addMeeting(5, '-2 days'), 101, $this->addPresenceType(2, 'Other', 7));
        $this->loginAdmin();

        $this->post('/coach-payments/pay/101');

        $this->assertFlashElement('flash/error');
        $this->assertSame(1, $this->openCount(101));
    }

    public function testDeletePaymentReopensThePresences(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 100, $this->main);
        $payment = $this->getTableLocator()->get('CoachPayments')->payOpen(100, 1);
        $this->loginAdmin();

        $this->post('/coach-payments/delete-payment/' . $payment->id);

        $this->assertRedirectContains('/coach-payments#coach-100');
        $this->assertSame(0, $this->getTableLocator()->get('CoachPayments')->find()->count());
        $this->assertSame(1, $this->openCount(100));
    }

    public function testDeletingAMeetingWithPaidPresencesIsRefused(): void
    {
        $meetingId = $this->addMeeting(1, '-2 days');
        $this->addCoachPresence($meetingId, 100, $this->main);
        $this->getTableLocator()->get('CoachPayments')->payOpen(100, 1);
        $this->loginAdmin();

        $this->post('/meetings/delete/' . $meetingId);

        $this->assertFlashElement('flash/error');
        $this->assertFlashMessage('This meeting has paid coach presences, it can\'t be deleted.');
        $this->assertTrue($this->getTableLocator()->get('Meetings')->exists(['id' => $meetingId]));
    }

    public function testAdminMenuLink(): void
    {
        $this->loginAdmin();
        $this->get('/coach-payments/settings');

        $this->assertResponseContains('href="/coach-payments"');
    }
}
