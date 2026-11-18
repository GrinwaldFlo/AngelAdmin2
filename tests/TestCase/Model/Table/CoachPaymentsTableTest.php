<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\CoachPayment;
use App\Model\Table\CoachPaymentsTable;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Payments of the coaches: only the due open presences of the coach and the site, reopened when deleted
 *
 * @uses \App\Model\Table\CoachPaymentsTable
 */
class CoachPaymentsTableTest extends TestCase
{
    use CoachPaymentDataTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    protected CoachPaymentsTable $CoachPayments;

    private int $main;
    private int $absent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CoachPayments = $this->getTableLocator()->get('CoachPayments');

        $this->addSite(2);
        $this->addTeam(5, 'Other city', true, 2);
        $this->addMember(100, [1]);
        $this->addCoach(1, 1);
        $this->addCoach(1, 100);
        $this->main = $this->addPresenceType(1, 'Main', 10);
        $this->absent = $this->addPresenceType(1, 'Absent', 0);
    }

    private function openCount(int $memberId): int
    {
        return $this->getTableLocator()->get('CoachPresences')->find()->where(['member_id' => $memberId, 'coach_payment_id IS' => null])->count();
    }

    public function testPayOpenPaysTheDuePresencesOfTheCoachAndSite(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-3 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->absent);
        $planned = $this->addCoachPresence($this->addMeeting(1, '+2 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 100, $this->main);
        $this->addCoach(5, 1);
        $otherSite = $this->addCoachPresence($this->addMeeting(5), 1, $this->addPresenceType(2, 'Other', 7));

        $payment = $this->CoachPayments->payOpen(1, 1);

        $this->assertInstanceOf(CoachPayment::class, $payment);
        $this->assertSame(20.0, (float)$payment->amount);
        $this->assertSame(1, $payment->site_id);
        $this->assertTrue($payment->paid_date->isToday());
        $presences = $this->getTableLocator()->get('CoachPresences');
        $paid = $presences->find()->where(['coach_payment_id' => $payment->id])->all();
        $this->assertCount(3, $paid);
        $this->assertEqualsCanonicalizing([10.0, 10.0, 0.0], $paid->map(fn ($presence) => (float)$presence->amount)->toList());
        $this->assertNull($presences->get($planned)->coach_payment_id);
        $this->assertNull($presences->get($otherSite)->coach_payment_id);
        $this->assertSame(1, $this->openCount(100));
        $this->assertSame([], $this->getTableLocator()->get('CoachPresences')->balances(1, 1)[1]['due']);
    }

    public function testPayOpenRefusedWhenNothingToPay(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->absent);
        $this->addCoachPresence($this->addMeeting(1, '+2 days'), 1, $this->main);

        $this->assertIsString($this->CoachPayments->payOpen(1, 1));
        $this->assertSame(0, $this->CoachPayments->find()->count());
        $this->assertSame(2, $this->openCount(1));
    }

    public function testPayOpenRefusedWhenTheBalanceChanged(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-1 days'), 1, $this->main);

        $this->assertIsString($this->CoachPayments->payOpen(1, 1, 10.0));
        $this->assertSame(0, $this->CoachPayments->find()->count());

        $this->assertInstanceOf(CoachPayment::class, $this->CoachPayments->payOpen(1, 1, 20.0));
    }

    public function testDeletePaymentReopensItsPresences(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->main);
        $payment = $this->CoachPayments->payOpen(1, 1);
        $this->assertSame(0, $this->openCount(1));

        $this->assertTrue($this->CoachPayments->delete($payment));

        $this->assertSame(1, $this->openCount(1));
        $presence = $this->getTableLocator()->get('CoachPresences')->find()->firstOrFail();
        $this->assertNull($presence->amount);
        $this->assertSame(10.0, $this->getTableLocator()->get('CoachPresences')->balances(1)[1]['dueTotal']);
    }

    public function testPaymentsOfSiteWithTheNumberOfPresences(): void
    {
        $this->addCoachPresence($this->addMeeting(1, '-3 days'), 1, $this->main);
        $this->addCoachPresence($this->addMeeting(1, '-2 days'), 1, $this->main);
        $this->CoachPayments->payOpen(1, 1);

        $payments = $this->CoachPayments->paymentsOfSite(1);

        $this->assertSame([1], array_keys($payments));
        $this->assertSame(2, (int)$payments[1][0]->presences_count);
        $this->assertSame([], $this->CoachPayments->paymentsOfSite(2));
        $this->assertSame([], $this->CoachPayments->paymentsOfSite(1, 100));
    }

    public function testQrMissing(): void
    {
        $member = $this->getTableLocator()->get('Members')->get(1);
        $member->address = '';

        $this->assertCount(2, $this->CoachPayments->qrMissing($member, null));

        $this->setAddress(1);
        $this->addCoachAccount(1, 1);
        $member = $this->getTableLocator()->get('Members')->get(1);
        $this->assertSame([], $this->CoachPayments->qrMissing($member, $this->getTableLocator()->get('CoachAccounts')->accountOf(1, 1)));
    }

    public function testQrCodeIsAnSvgDataUri(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed to create the QR code');
        }
        $this->setAddress(1);
        $this->setSiteAddress();
        $this->addCoachAccount(1, 1);

        $uri = $this->CoachPayments->qrCode(
            $this->getTableLocator()->get('Members')->get(1),
            $this->getTableLocator()->get('CoachAccounts')->accountOf(1, 1),
            $this->getTableLocator()->get('Sites')->get(1),
            25.0,
            'Coach 2026'
        );

        $this->assertStringStartsWith('data:image/svg+xml', $uri);
    }
}
