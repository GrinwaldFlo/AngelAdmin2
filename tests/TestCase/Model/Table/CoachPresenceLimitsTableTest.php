<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CoachPresenceLimitsTable;
use App\Test\TestSuite\CoachPaymentDataTrait;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * Limits of the presence types per team, saved from the settings page
 *
 * Site 1: team 1 and team 2, types Main and Secondary. Site 2: team 5, type Other.
 *
 * @uses \App\Model\Table\CoachPresenceLimitsTable
 */
class CoachPresenceLimitsTableTest extends TestCase
{
    use CoachPaymentDataTrait;
    use TestDataTrait;

    protected array $fixtures = self::COACH_FIXTURES;

    protected CoachPresenceLimitsTable $Limits;

    private int $main;
    private int $secondary;
    private int $otherSite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Limits = $this->getTableLocator()->get('CoachPresenceLimits');

        $this->addSite(2);
        $this->addTeam(2, 'Second team');
        $this->addTeam(5, 'Other city', true, 2);
        $this->main = $this->addPresenceType(1, 'Main', 10);
        $this->secondary = $this->addPresenceType(1, 'Secondary', 5);
        $this->otherSite = $this->addPresenceType(2, 'Other', 7);
    }

    public function testSaveLimitsCreatesChangesAndRemoves(): void
    {
        $this->assertTrue($this->Limits->saveLimits(
            [1 => [$this->main => '1', $this->secondary => ''], 2 => [$this->secondary => '0']],
            [1, 2],
            [$this->main, $this->secondary],
        ));
        $this->assertSame([1 => [$this->main => 1], 2 => [$this->secondary => 0]], $this->Limits->limitsOfTeams([1, 2]));

        $this->assertTrue($this->Limits->saveLimits(
            [1 => [$this->main => '', $this->secondary => '3'], 2 => [$this->secondary => '0']],
            [1, 2],
            [$this->main, $this->secondary],
        ));
        $this->assertSame([1 => [$this->secondary => 3], 2 => [$this->secondary => 0]], $this->Limits->limitsOfTeams([1, 2]));
        $this->assertSame(2, $this->Limits->find()->count());
    }

    public function testSaveLimitsIgnoresTheTeamsAndTypesNotGiven(): void
    {
        $this->addPresenceLimit(5, $this->otherSite, 2);

        $this->assertTrue($this->Limits->saveLimits(
            [1 => [$this->main => '1', $this->otherSite => '4'], 5 => [$this->otherSite => '']],
            [1],
            [$this->main],
        ));

        $this->assertSame([1 => [$this->main => 1], 5 => [$this->otherSite => 2]], $this->Limits->limitsOfTeams([1, 5]));
    }

    public function testInvalidValueSavesNothing(): void
    {
        $this->addPresenceLimit(1, $this->main, 2);

        foreach (['-1', 'abc', (string)(CoachPresenceLimitsTable::MAX + 1), '1.5'] as $value) {
            $this->assertFalse($this->Limits->saveLimits(
                [1 => [$this->main => '', $this->secondary => $value]],
                [1],
                [$this->main, $this->secondary],
            ), $value);
            $this->assertSame([$this->main => 2], $this->Limits->limitsOfTeam(1), $value);
        }
    }

    public function testLimitsRemovedWithTheirTypeAndTeam(): void
    {
        $this->addPresenceLimit(1, $this->main, 1);
        $this->addPresenceLimit(2, $this->secondary, 1);

        $types = $this->getTableLocator()->get('CoachPresenceTypes');
        $types->deleteOrFail($types->get($this->main));
        $teams = $this->getTableLocator()->get('Teams');
        $teams->deleteOrFail($teams->get(2));

        $this->assertSame(0, $this->Limits->find()->count());
    }
}
