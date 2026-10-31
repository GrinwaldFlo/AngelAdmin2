<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Meeting;
use App\Model\Entity\Presence;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\Presence
 */
class PresenceTest extends TestCase
{
    private function presence(string $date): Presence
    {
        return new Presence(['meeting' => new Meeting(['meeting_date' => new DateTime($date)])]);
    }

    public function testStates(): void
    {
        $this->assertSame([Presence::PRESENT, Presence::ABSENT, Presence::EXCUSED, Presence::LATE], array_keys(Presence::states()));
        $this->assertTrue(Presence::isValidState(Presence::LATE));
        $this->assertFalse(Presence::isValidState(Presence::NONE));
        $this->assertFalse(Presence::isValidState(9));
    }

    public function testStatusHtml(): void
    {
        $this->assertSame('success', (new Presence(['state' => Presence::PRESENT]))->StatusHtml);
        $this->assertSame('danger', (new Presence(['state' => Presence::ABSENT]))->StatusHtml);
        $this->assertSame('primary', (new Presence(['state' => Presence::EXCUSED]))->StatusHtml);
        $this->assertSame('warning', (new Presence(['state' => Presence::LATE]))->StatusHtml);
        $this->assertSame('black', (new Presence(['state' => Presence::NONE]))->StatusHtml);
    }

    public function testSeason(): void
    {
        $this->assertSame('2025-2026', $this->presence('2025-09-01 18:00')->Season('01.09'));
        $this->assertSame('2024-2025', $this->presence('2025-08-31 18:00')->Season('01.09'));
        $this->assertSame('2025-2026', $this->presence('2025-12-30 18:00')->Season('01.09'));
    }

    /**
     * Around the new year the ISO week year (YYYY) is not the calendar year
     */
    public function testSeasonStartingInJanuary(): void
    {
        // 1 January 2027 is in the last week of 2026, 29 December 2025 in the first week of 2026
        $this->assertSame('2027-2028', $this->presence('2027-01-01 10:00')->Season('01.01'));
        $this->assertSame('2025-2026', $this->presence('2025-12-29 10:00')->Season('01.01'));
    }
}
