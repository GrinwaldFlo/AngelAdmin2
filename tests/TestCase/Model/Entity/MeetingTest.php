<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Meeting;
use App\Model\Entity\Presence;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\Meeting
 */
class MeetingTest extends TestCase
{
    public function testCountsAlreadyComputed(): void
    {
        $meeting = new Meeting(['present' => 3, 'absent' => 1, 'excused' => 0, 'late' => 2]);

        $this->assertTrue($meeting->hasCounts());
        $this->assertSame([Presence::PRESENT => 3, Presence::ABSENT => 1, Presence::EXCUSED => 0, Presence::LATE => 2], $meeting->counts());
        $this->assertSame('3/1/0/2', $meeting->PresencesStr);
    }

    public function testHasCounts(): void
    {
        $this->assertFalse((new Meeting(['present' => -1, 'absent' => 0, 'excused' => 0, 'late' => 0]))->hasCounts());
        $this->assertFalse((new Meeting(['present' => 0, 'absent' => 0, 'excused' => 0, 'late' => -1]))->hasCounts());
    }

    public function testSetCounts(): void
    {
        $meeting = new Meeting();

        $meeting->setCounts([Presence::PRESENT => '4', Presence::LATE => 1]);

        $this->assertSame([Presence::PRESENT => 4, Presence::ABSENT => 0, Presence::EXCUSED => 0, Presence::LATE => 1], $meeting->counts());
    }

    public function testWebUrl(): void
    {
        $this->assertTrue(Meeting::isWebUrl('https://example.com/event'));
        $this->assertTrue(Meeting::isWebUrl('HTTP://example.com'));
        $this->assertFalse(Meeting::isWebUrl('javascript:alert(1)'));
        $this->assertFalse(Meeting::isWebUrl('ftp://example.com'));
        $this->assertFalse(Meeting::isWebUrl('https://'));
        $this->assertFalse(Meeting::isWebUrl(null));
        $this->assertSame('https://example.com', (new Meeting(['url' => 'https://example.com']))->webUrl());
        $this->assertNull((new Meeting(['url' => 'data:text/html,x']))->webUrl());
    }
}
