<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\CoachAccount;
use App\Model\Entity\CoachPayment;
use App\Policy\CoachAccountPolicy;
use App\Policy\CoachPaymentPolicy;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Policy\CoachPaymentPolicy
 * @uses \App\Policy\CoachAccountPolicy
 */
class CoachPaymentPolicyTest extends TestCase
{
    use CoachPaymentDataTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    public function testAdminOnly(): void
    {
        $policy = new CoachPaymentPolicy();

        $this->assertTrue($policy->canAdmin($this->identity(['Admin']), new CoachPayment()));
        $this->assertFalse($policy->canAdmin($this->identity(['MemberViewAll', 'MemberEditAll', 'BillViewAll', 'BillEditAll']), new CoachPayment()));
    }

    public function testCoachSetsTheirOwnIbanOnlyInTheirSites(): void
    {
        $this->addSite(2);
        $this->addTeam(5, 'Other city', true, 2);
        $this->addCoach(1, 1);
        $policy = new CoachAccountPolicy();
        $identity = $this->identity([]);

        $this->assertTrue($policy->canSetOwnIban($identity, $this->account(1, 1)));
        $this->assertFalse($policy->canSetOwnIban($identity, $this->account(1, 2)), 'not coach in site 2');
        $this->assertFalse($policy->canSetOwnIban($identity, $this->account(100, 1)), 'another member');
    }

    public function testNotACoach(): void
    {
        $this->assertFalse((new CoachAccountPolicy())->canSetOwnIban($this->identity(['Admin']), $this->account(1, 1)));
    }

    private function account(int $memberId, int $siteId): CoachAccount
    {
        return $this->entity(CoachAccount::class, ['member_id' => $memberId, 'site_id' => $siteId], true);
    }
}
