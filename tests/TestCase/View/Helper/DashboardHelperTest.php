<?php

declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\DashboardHelper;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * @uses \App\View\Helper\DashboardHelper
 */
class DashboardHelperTest extends TestCase
{
    private DashboardHelper $Dashboard;

    protected function setUp(): void
    {
        parent::setUp();
        I18n::setLocale('en');
        $this->Dashboard = new DashboardHelper(new View());
    }

    protected function tearDown(): void
    {
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    public function testKpiEscapesTexts(): void
    {
        $html = $this->Dashboard->kpi('<b>Members</b>', '12 & more', 'sub <i>');

        $this->assertStringContainsString('<div class="dash-kpi-label">&lt;b&gt;Members&lt;/b&gt;</div>', $html);
        $this->assertStringContainsString('<div class="dash-kpi-value">12 &amp; more</div>', $html);
        $this->assertStringContainsString('<div class="dash-kpi-sub">sub &lt;i&gt;</div>', $html);
        $this->assertStringNotContainsString('<progress', $html);
    }

    public function testKpiWithProgressWithoutSub(): void
    {
        $html = $this->Dashboard->kpi('Paid', '50 %', '', [5, 10], 'warning');

        $this->assertStringContainsString('<progress class="dash-progress dash-progress-warning" value="5" max="10" aria-label="Paid">', $html);
        $this->assertStringNotContainsString('dash-kpi-sub', $html);
    }

    public function testProgressBounds(): void
    {
        $this->assertSame(
            '<progress class="dash-progress" value="0" max="1" aria-label="x"></progress>',
            $this->Dashboard->progress(-3, 0, 'x')
        );
    }

    public function testBarsRelativeToTheBiggest(): void
    {
        $html = $this->Dashboard->bars(['A' => 2, 'B' => 4], 'success');

        $this->assertSame(2, substr_count($html, 'class="dash-bar"'));
        $this->assertStringContainsString('value="2" max="4"', $html);
        $this->assertStringContainsString('value="4" max="4"', $html);
        $this->assertStringContainsString('<span class="dash-bar-text">4</span>', $html);
    }

    public function testBarsOfZeros(): void
    {
        $this->assertStringContainsString('value="0" max="1"', $this->Dashboard->bars(['A' => 0]));
        $this->assertSame('', $this->Dashboard->bars([]));
    }

    public function testPercent(): void
    {
        $this->assertSame(33, $this->Dashboard->percent(1, 3));
        $this->assertSame(67, $this->Dashboard->percent(2, 3));
        $this->assertNull($this->Dashboard->percent(1, 0));
        $this->assertSame('33 %', $this->Dashboard->percentStr(33));
        $this->assertSame('-', $this->Dashboard->percentStr(null));
    }

    public function testMoney(): void
    {
        $this->assertSame('1,235 CHF', $this->Dashboard->money(1234.6));
    }

    public function testRateVariant(): void
    {
        $this->assertSame('', $this->Dashboard->rateVariant(null));
        $this->assertSame('success', $this->Dashboard->rateVariant(80));
        $this->assertSame('warning', $this->Dashboard->rateVariant(79));
        $this->assertSame('warning', $this->Dashboard->rateVariant(50));
        $this->assertSame('danger', $this->Dashboard->rateVariant(49));
    }
}
