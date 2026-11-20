<?php

declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Entity\Meeting;
use Cake\View\Cell;

/**
 * Coach presence module: block of the coaches on the attendance page of a meeting
 * The page is already authorized (MeetingPolicy::canView), saving checks canSetPresence
 */
class CoachPresencesCell extends Cell
{
    public function display(Meeting $meeting): void
    {
        $rows = $this->fetchTable('CoachPresences')->rowsOfMeeting($meeting);
        $siteId = (int)$this->fetchTable('Teams')->get($meeting->team_id)->site_id;
        $types = $this->fetchTable('CoachPresenceTypes')->find('ofSite', siteId: $siteId, activeOnly: false)->all()->indexBy('id')->toArray();

        $this->set(compact('meeting', 'rows', 'types'));
    }
}
