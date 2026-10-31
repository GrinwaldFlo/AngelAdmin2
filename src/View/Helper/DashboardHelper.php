<?php
namespace App\View\Helper;

use Cake\View\Helper;

/**
 * Building blocks of the admin dashboard: key figures and CSS bars
 *
 * @property \Cake\View\Helper\NumberHelper $Number
 */
class DashboardHelper extends Helper
{
    public array $helpers = ['Number'];

    /**
     * Key figure card
     * @param string $label Title of the card
     * @param string $value Main value (escaped)
     * @param string $sub Detail line (escaped)
     * @param array|null $progress [value, max] to show a progress bar
     * @param string $variant Color of the progress bar: '', 'success', 'warning', 'danger'
     */
    public function kpi(string $label, string $value, string $sub = '', ?array $progress = null, string $variant = ''): string
    {
        $html = '<div class="dash-kpi-label">' . h($label) . '</div>'
            . '<div class="dash-kpi-value">' . h($value) . '</div>';
        if ($progress !== null) {
            $html .= $this->progress($progress[0], $progress[1], $label, $variant);
        }
        if ($sub !== '') {
            $html .= '<div class="dash-kpi-sub">' . h($sub) . '</div>';
        }
        return '<div class="dash-card dash-kpi">' . $html . '</div>';
    }

    /**
     * Bar with a label on the left and a text on the right
     */
    public function bar(string $label, int|float $value, int|float $max, string $text = '', string $variant = ''): string
    {
        return '<div class="dash-bar">'
            . '<span class="dash-bar-label">' . h($label) . '</span>'
            . $this->progress($value, $max, $label, $variant)
            . '<span class="dash-bar-text">' . h($text) . '</span>'
            . '</div>';
    }

    /**
     * Bars for each value of a list, relative to the biggest value
     * @param array $values [label => value]
     * @param string $variant Color of the bars
     */
    public function bars(array $values, string $variant = ''): string
    {
        $max = max([1, ...array_values($values)]);
        $html = '';
        foreach ($values as $label => $value) {
            $html .= $this->bar((string)$label, $value, $max, (string)$value, $variant);
        }
        return $html;
    }

    public function progress(int|float $value, int|float $max, string $label, string $variant = ''): string
    {
        $class = 'dash-progress' . ($variant !== '' ? ' dash-progress-' . $variant : '');
        return sprintf(
            '<progress class="%s" value="%s" max="%s" aria-label="%s"></progress>',
            $class,
            max(0, $value),
            max(1, $max),
            h($label)
        );
    }

    /**
     * @return int|null Percentage of $value in $max, null if $max is 0
     */
    public function percent(int|float $value, int|float $max): ?int
    {
        return $max > 0 ? (int)round($value * 100 / $max) : null;
    }

    public function percentStr(?int $percent): string
    {
        return $percent === null ? '-' : $percent . ' %';
    }

    public function money(int|float $amount): string
    {
        return $this->Number->format($amount, ['places' => 0, 'precision' => 0]) . ' CHF';
    }

    /**
     * Bar color for a rate in percent: good, medium or bad
     */
    public function rateVariant(?int $percent): string
    {
        if ($percent === null) {
            return '';
        }
        return $percent >= 80 ? 'success' : ($percent >= 50 ? 'warning' : 'danger');
    }
}
