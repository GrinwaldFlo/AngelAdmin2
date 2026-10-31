<?php

declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Model\Entity\Meeting;
use App\Model\Entity\Presence;
use App\View\Helper\MeetingHelper;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * @uses \App\View\Helper\MeetingHelper
 */
class MeetingHelperTest extends TestCase
{
    private MeetingHelper $Meeting;

    protected function setUp(): void
    {
        parent::setUp();
        I18n::setLocale('en');
        $this->Meeting = new MeetingHelper(new View());
    }

    protected function tearDown(): void
    {
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    /**
     * Meeting with its attendance counters already computed (no database access)
     */
    private function meeting(string $date, array $fields = []): Meeting
    {
        return new Meeting($fields + [
            'id' => 1, 'meeting_date' => new DateTime($date), 'present' => 0, 'absent' => 0, 'excused' => 0, 'late' => 0,
            'big_event' => false, 'doodle' => false, 'url' => null,
        ], ['guard' => false, 'markNew' => false]);
    }

    public function testIcon(): void
    {
        $this->assertSame(
            '<span class="material-symbols-outlined mt-icon big" aria-hidden="true">star</span>',
            $this->Meeting->icon('star', 'big')
        );
    }

    public function testDateBlock(): void
    {
        $html = $this->Meeting->dateBlock($this->meeting('2026-11-10 18:00'));
        $this->assertStringContainsString('<div class="mt-date">', $html);
        $this->assertStringContainsString('<span class="mt-date-day">10</span>', $html);
        $this->assertStringContainsString('<span class="mt-date-weekday">Tue</span>', $html);

        $today = $this->meeting('today 18:00');
        $this->assertTrue($this->Meeting->isToday($today));
        $this->assertStringContainsString('<div class="mt-date is-today">', $this->Meeting->dateBlock($today));
    }

    public function testBadges(): void
    {
        $html = $this->Meeting->badges($this->meeting('2026-11-10 18:30'));
        $this->assertStringContainsString('18:30', $html);
        $this->assertSame(1, substr_count($html, '<span class="mt-badge'));

        $html = $this->Meeting->badges($this->meeting('2026-11-10 18:30', ['big_event' => true, 'doodle' => true, 'url' => 'https://example.com']));
        $this->assertStringContainsString('mt-badge-event', $html);
        $this->assertStringContainsString('Doodle', $html);
        $this->assertStringContainsString('Link', $html);
    }

    public function testBadgesHideNonWebLink(): void
    {
        $html = $this->Meeting->badges($this->meeting('2026-11-10 18:30', ['url' => 'javascript:alert(1)']));

        $this->assertStringNotContainsString('Link', $html);
    }

    public function testCounts(): void
    {
        $this->assertStringContainsString('No attendance yet', $this->Meeting->counts($this->meeting('2026-11-10')));

        $html = $this->Meeting->counts($this->meeting('2026-11-10', ['present' => 5, 'late' => 2]));
        $this->assertStringContainsString('mt-is-present', $html);
        $this->assertStringContainsString('mt-is-late', $html);
        $this->assertStringNotContainsString('mt-is-absent', $html);
        $this->assertStringContainsString('</span>5</span>', $html);
    }

    public function testStateButtons(): void
    {
        $html = $this->Meeting->stateButtons(Presence::EXCUSED);

        $this->assertSame(4, substr_count($html, '<button'));
        $this->assertSame(1, substr_count($html, 'aria-pressed="true"'));
        $this->assertStringContainsString('data-state="' . Presence::EXCUSED . '" aria-pressed="true"', $html);
        $this->assertSame(0, substr_count($this->Meeting->stateButtons(Presence::NONE), 'aria-pressed="true"'));
    }

    public function testFilters(): void
    {
        $filters = $this->Meeting->filters();

        $this->assertSame(['all', (string)Presence::NONE, ...array_map('strval', array_keys(Presence::states()))], array_map('strval', array_keys($filters)));
        $this->assertSame('none', $filters[Presence::NONE]['key']);
    }

    public function testFilterButtonAndLegend(): void
    {
        $html = $this->Meeting->filterButton('all', 'All <members>', 'all', 'groups');
        $this->assertStringContainsString('data-filter="all"', $html);
        $this->assertStringContainsString('All &lt;members&gt;', $html);

        $legend = $this->Meeting->legend();
        $this->assertSame(count($this->Meeting->filters()), substr_count($legend, 'mt-legend-item'));
    }
}
