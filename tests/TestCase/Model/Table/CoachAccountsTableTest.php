<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CoachAccountsTable;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * IBAN of the coaches (one per site) and coaches of a site
 *
 * @uses \App\Model\Table\CoachAccountsTable
 */
class CoachAccountsTableTest extends TestCase
{
    use CoachPaymentDataTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    protected CoachAccountsTable $CoachAccounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CoachAccounts = $this->getTableLocator()->get('CoachAccounts');
        $this->addSite(2);
        $this->addTeam(5, 'Other city', true, 2);
    }

    public function testSetIbanNormalizesAndReplaces(): void
    {
        $account = $this->CoachAccounts->setIban(1, 1, 'ch93 0076 2011 6238 5295 7');
        $this->assertFalse($account->hasErrors());
        $this->assertSame(self::IBAN, $this->CoachAccounts->accountOf(1, 1)->iban);

        $this->CoachAccounts->setIban(1, 1, 'LI21 0881 0000 2324 013A A');

        $this->assertSame(1, $this->CoachAccounts->find()->count());
        $this->assertSame('LI21088100002324013AA', $this->CoachAccounts->accountOf(1, 1)->iban);
    }

    public function testOneIbanPerSite(): void
    {
        $this->CoachAccounts->setIban(1, 1, self::IBAN);
        $this->CoachAccounts->setIban(1, 2, 'LI21088100002324013AA');

        $this->assertSame(self::IBAN, $this->CoachAccounts->accountOf(1, 1)->iban);
        $this->assertSame('LI21088100002324013AA', $this->CoachAccounts->accountOf(1, 2)->iban);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIbans(): array
    {
        return [
            'empty' => [''],
            'wrong checksum' => ['CH9300762011623852958'],
            'foreign' => ['DE89370400440532013000'],
            'QR-IBAN' => ['CH4431999123000889012'],
            'text' => ['not an iban'],
        ];
    }

    #[DataProvider('invalidIbans')]
    public function testSetIbanRefusesInvalid(string $iban): void
    {
        $account = $this->CoachAccounts->setIban(1, 1, $iban);

        $this->assertTrue($account->hasErrors());
        $this->assertNull($this->CoachAccounts->accountOf(1, 1));
    }

    public function testInvalidIbanKeepsTheOldOne(): void
    {
        $this->CoachAccounts->setIban(1, 1, self::IBAN);

        $this->CoachAccounts->setIban(1, 1, 'DE89370400440532013000');

        $this->assertSame(self::IBAN, $this->CoachAccounts->accountOf(1, 1)->iban);
    }

    public function testIbanIsMaskedAndNeverSerialized(): void
    {
        $this->CoachAccounts->setIban(1, 1, self::IBAN);
        $account = $this->CoachAccounts->accountOf(1, 1);

        $this->assertSame('•••• 2957', $account->masked_iban);
        $this->assertArrayNotHasKey('iban', $account->toArray());
        $this->assertStringNotContainsString('762011623852', (string)json_encode($account));
    }

    public function testRemoveIban(): void
    {
        $this->CoachAccounts->setIban(1, 1, self::IBAN);

        $this->assertTrue($this->CoachAccounts->removeIban(1, 1));
        $this->assertNull($this->CoachAccounts->accountOf(1, 1));
        $this->assertFalse($this->CoachAccounts->removeIban(1, 1));
    }

    public function testSiteIdsOfCoachedTeamsPresencesAndAccounts(): void
    {
        $this->assertSame([], $this->CoachAccounts->siteIdsOf(1));

        $this->addCoach(1, 1);
        $this->assertSame([1], $this->CoachAccounts->siteIdsOf(1));

        $this->addSite(3);
        $this->addTeam(6, 'Third city', true, 3);
        $this->addCoachPresence($this->addMeeting(5), 1, $this->addPresenceType(2, 'Other', 7));
        $this->CoachAccounts->setIban(1, 3, self::IBAN);

        $this->assertEqualsCanonicalizing([1, 2, 3], $this->CoachAccounts->siteIdsOf(1));
    }

    public function testCoachesOfSite(): void
    {
        $this->addMember(100, [1]);
        $this->addMember(101, [5]);
        $this->addMember(102, [1]);
        $this->addCoach(1, 100);
        $this->addCoach(5, 101);
        $this->CoachAccounts->setIban(102, 1, self::IBAN);

        $this->assertSame([100, 102], array_keys($this->CoachAccounts->coachesOfSite(1)));
        $this->assertSame([101], array_keys($this->CoachAccounts->coachesOfSite(2)));
        $this->assertSame([100 => ['Lorem ipsum dolor sit amet']], $this->CoachAccounts->coachedTeamNames(1));
    }
}
