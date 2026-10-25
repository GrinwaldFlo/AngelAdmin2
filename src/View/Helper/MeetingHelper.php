<?php
namespace App\View\Helper;

use App\Model\Entity\Meeting;
use App\Model\Entity\Presence;
use Cake\View\Helper;

/**
 * Building blocks of the meetings / attendance pages
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class MeetingHelper extends Helper
{
    public array $helpers = ['Html'];

    /**
     * Material symbol icon, hidden from screen readers
     */
    public function icon(string $name, string $class = ''): string
    {
        return '<span class="material-symbols-outlined mt-icon ' . $class . '" aria-hidden="true">' . h($name) . '</span>';
    }

    public function isToday(Meeting $meeting): bool
    {
        return $meeting->meeting_date->isToday();
    }

    /**
     * Calendar-like block with the day number and the weekday
     */
    public function dateBlock(Meeting $meeting): string
    {
        $today = $this->isToday($meeting) ? ' is-today' : '';
        return '<div class="mt-date' . $today . '">'
            . '<span class="mt-date-day">' . $meeting->meeting_date->i18nFormat('dd') . '</span>'
            . '<span class="mt-date-weekday">' . h($meeting->meeting_date->i18nFormat('EEE')) . '</span>'
            . '</div>';
    }

    /**
     * Small badges: time, big event, doodle, external link
     */
    public function badges(Meeting $meeting): string
    {
        $html = '<span class="mt-badge">' . $this->icon('schedule') . $meeting->meeting_date->i18nFormat('HH:mm') . '</span>';
        if ($meeting->big_event) {
            $html .= '<span class="mt-badge mt-badge-event">' . $this->icon('star') . h(__('Big event')) . '</span>';
        }
        if ($meeting->doodle) {
            $html .= '<span class="mt-badge">' . $this->icon('how_to_reg') . h(__('Doodle')) . '</span>';
        }
        if (!empty($meeting->url)) {
            $html .= '<span class="mt-badge">' . $this->icon('link') . h(__('Link')) . '</span>';
        }
        return '<div class="mt-badges">' . $html . '</div>';
    }

    /**
     * Attendance counters of a meeting (only the states that were used)
     */
    public function counts(Meeting $meeting): string
    {
        $html = '';
        $states = Presence::states();
        foreach ($meeting->counts() as $state => $count) {
            if ($count > 0) {
                $html .= '<span class="mt-count-chip mt-is-' . $states[$state]['key'] . '" title="' . h($states[$state]['label']) . '">'
                    . $this->icon($states[$state]['icon']) . $count . '</span>';
            }
        }
        if ($html === '') {
            return '<div class="mt-counts mt-muted">' . h(__('No attendance yet')) . '</div>';
        }
        return '<div class="mt-counts">' . $html . '</div>';
    }

    /**
     * The 4 state buttons of a member on the attendance page
     * @param int $current State of the member
     */
    public function stateButtons(int $current): string
    {
        $html = '';
        foreach (Presence::states() as $state => $info) {
            $html .= '<button type="button" class="mt-state mt-is-' . $info['key'] . '" data-state="' . $state . '"'
                . ' aria-pressed="' . ($state === $current ? 'true' : 'false') . '"'
                . ' title="' . h($info['label']) . '" aria-label="' . h($info['label']) . '">'
                . $this->icon($info['icon']) . '</button>';
        }
        return '<div class="mt-states" role="group">' . $html . '</div>';
    }

    /**
     * Counter / filter button of the attendance page
     * @param string $filter 'all' or a state
     */
    public function filterButton(string $filter, string $label, string $key, string $icon): string
    {
        return '<button type="button" class="mt-filter mt-is-' . $key . '" data-filter="' . h($filter) . '" title="' . h($label) . '">'
            . $this->icon($icon) . '<span class="mt-count">0</span>'
            . '<span class="visually-hidden">' . h($label) . '</span></button>';
    }
}
