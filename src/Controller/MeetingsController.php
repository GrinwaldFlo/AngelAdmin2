<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Entity\Presence;
use App\Model\Table\MeetingsTable;
use Cake\I18n\DateTime;

/**
 * Meetings Controller
 *
 * @property \App\Model\Table\MeetingsTable $Meetings
 */
class MeetingsController extends AppController
{
  private const FORM_FIELDS = ['meeting_date', 'team_id', 'name', 'big_event', 'url', 'doodle'];

  /** Number of meetings shown before "show more" */
  private const PAGE_SIZE = 20;
  private const MAX_PAGE_SIZE = 500;

  /**
   * List of the meetings of one team
   * @param int|null $teamId null = last selected team
   * @param int|null $meetingFilter MeetingsTable::FILTER_UPCOMING or FILTER_PAST, null = last selected
   */
  public function index(int|null $teamId = null, int|null $meetingFilter = null)
  {
    $this->Authorization->authorize($this->Meetings->newEmptyEntity(), 'viewall');
    $teams = $this->getTeamsActiv($this->getSiteId());
    $teamId = $this->selectTeam($teamId, $teams);

    $meetingFilter = $this->getPrefSession('meetingFilter', $meetingFilter, MeetingsTable::FILTER_UPCOMING);
    if ($meetingFilter != MeetingsTable::FILTER_PAST) {
      $meetingFilter = MeetingsTable::FILTER_UPCOMING;
      $this->Session->write('meetingFilter', $meetingFilter);
    }

    $limit = min(max((int)$this->request->getQuery('n', self::PAGE_SIZE), self::PAGE_SIZE), self::MAX_PAGE_SIZE);
    $meetings = [];
    $hasMore = false;
    if ($teamId > 0) {
      $meetings = $this->Meetings->find('Meetings', teamId: $teamId, siteId: 0, meetingFilter: $meetingFilter)
        ->contain('Teams')
        ->orderBy(['Meetings.meeting_date' => $meetingFilter == MeetingsTable::FILTER_PAST ? 'DESC' : 'ASC'])
        ->limit($limit + 1)
        ->toArray();
      $hasMore = count($meetings) > $limit;
      $meetings = array_slice($meetings, 0, $limit);
      $this->Meetings->loadCounts($meetings);
    }

    $this->set(compact('meetings', 'teamId', 'meetingFilter', 'teams', 'limit', 'hasMore'));
  }

  /**
   * Attendance page of a meeting
   */
  public function view(int|null $id = null)
  {
    $meeting = $this->Meetings->get($id, contain: ['Teams', 'Presences.Members']);
    $this->Authorization->authorize($meeting, 'view');
    // Going back to the list shows the team of this meeting
    $this->Session->write('teamId', $meeting->team_id);

    $attendance = $this->Meetings->attendanceList($meeting, $this->getMembers($meeting->team_id, 1));
    $neighbours = $this->Meetings->neighbours($meeting);

    $this->set(compact('meeting', 'attendance', 'neighbours'));
  }

  /**
   * Single meetings are created with the same form as the recurring ones
   */
  public function add()
  {
    $this->Authorization->authorize($this->Meetings->newEmptyEntity(), 'edit');
    return $this->redirect(['action' => 'addMultiple']);
  }

  /**
   * Create one meeting or a weekly series of meetings
   */
  public function addMultiple()
  {
    $meeting = $this->Meetings->newEmptyEntity();
    $this->Authorization->authorize($meeting, 'edit');
    $teams = $this->getTeamsActiv($this->getSiteId());

    if ($this->request->is('post')) {
      $data = $this->request->getData();
      $dates = $this->meetingDates($data);
      $common = [
        'team_id' => $data['team_id'] ?? null,
        'name' => $data['name'] ?? '',
        'big_event' => $data['big_event'] ?? false,
        'doodle' => $data['doodle'] ?? false,
        'url' => $data['url'] ?? null,
      ];
      // Validate the common fields once, on the first date, to show the errors in the form
      $meeting = $this->Meetings->newEntity($common + ['meeting_date' => $dates[0] ?? null]);
      // Coaches only create meetings for their teams
      $this->Authorization->authorize($meeting, 'edit');

      if (empty($dates)) {
        $this->Flash->error(__('Choose at least one date and a time.'));
      } elseif ($meeting->hasErrors()) {
        $this->Flash->error(__('The meeting could not be saved.'));
      } else {
        $meetings = array_map(fn(DateTime $date) => $this->Meetings->newEntity($common + ['meeting_date' => $date]), $dates);
        if ($this->Meetings->saveMany($meetings)) {
          $count = count($meetings);
          $this->Flash->success(__n('{0} meeting has been saved.', '{0} meetings have been saved.', $count, $count));
          return $this->redirect(['action' => 'index', (int)$common['team_id'], MeetingsTable::FILTER_UPCOMING]);
        }
        $this->Flash->error(__('No meeting has been saved.'));
      }
    } else {
      $meeting->team_id = PrefResult::validateTeamId($this->Session->read('teamId'), $teams);
    }

    $seasonEnd = (clone $this->config['dateSeasonStart'])->modify('+1 year -1 day');
    $today = new \DateTime('today');
    if ($seasonEnd < $today) {
      $seasonEnd = (clone $today)->modify('+1 year');
    }

    $this->set(compact('meeting', 'teams', 'today', 'seasonEnd'));
  }

  /**
   * Edit method
   */
  public function edit(int|null $id = null)
  {
    $meeting = $this->Meetings->get($id, contain: ['Teams']);
    $this->Authorization->authorize($meeting, 'edit');

    if ($this->request->is(['patch', 'post', 'put'])) {
      // Not the team itself nor the presences (set on the attendance page)
      $meeting = $this->Meetings->patchEntity($meeting, $this->request->getData(), ['fields' => self::FORM_FIELDS]);
      // The team may have changed: coaches only move it to one of their teams
      $this->Authorization->authorize($meeting, 'edit');
      if ($this->Meetings->save($meeting)) {
        $this->Flash->success(__('The meeting has been saved.'));

        return $this->redirect(['action' => 'view', $meeting->id]);
      }
      $this->Flash->error(__('The meeting could not be saved.'));
    }
    // The current team stays selectable even if it is not active anymore
    $teams = $this->getTeamsActiv($this->getSiteId()) + [$meeting->team_id => $meeting->team->name];
    $this->set(compact('meeting', 'teams'));
  }

  /**
   * Delete method
   */
  public function delete(int|null $id = null)
  {
    $this->request->allowMethod(['post', 'delete']);
    $meeting = $this->Meetings->get($id);
    $this->Authorization->authorize($meeting, 'edit');
    if ($this->Meetings->delete($meeting)) {
      $this->Flash->success(__('The meeting has been deleted.'));
    } else {
      $this->Flash->error(__('The meeting could not be deleted.'));
    }

    return $this->redirect(['action' => 'index', $meeting->team_id]);
  }

  public function join(int|null $id = null)
  {
    $this->request->allowMethod(['post', 'delete']);
    $meeting = $this->Meetings->get($id);
    $this->Authorization->authorize($meeting, 'join');
    $this->Meetings->Presences->setState($meeting, [$this->curMember->id], Presence::PRESENT);

    $this->Flash->success(__('You are now registered'));
    return $this->redirect('/');
  }

  /**
   * Team shown in the list: the requested one, else the last selected, else the only active one
   * @param int|null $teamId null = last selected team
   * @param array $teams Active teams [id => name]
   * @return int 0 = no team selected yet
   */
  private function selectTeam(int|null $teamId, array $teams): int
  {
    $teamId = PrefResult::validateTeamId($this->getPrefSession('teamId', $teamId, 0), $teams);
    if ($teamId == 0 && count($teams) == 1) {
      $teamId = (int)array_key_first($teams);
      $this->Session->write('teamId', $teamId);
    }
    return $teamId;
  }

  /**
   * Dates of the meetings to create from the add form
   * @param array $data mode = 'once' (date) or 'weekly' (dates[]), time = 'HH:MM', dates as 'YYYY-MM-DD'
   * @return DateTime[] sorted, invalid dates are ignored
   */
  private function meetingDates(array $data): array
  {
    $days = ($data['mode'] ?? 'weekly') == 'once' ? [$data['date'] ?? ''] : (array)($data['dates'] ?? []);
    $time = is_string($data['time'] ?? null) ? $data['time'] : '';

    $dates = [];
    foreach (array_unique(array_filter($days, 'is_string')) as $day) {
      try {
        $date = DateTime::createFromFormat('!Y-m-d H:i', $day . ' ' . $time);
      } catch (\InvalidArgumentException) {
        continue;
      }
      // Reject overflowing values like 2030-02-31
      if ($date->format('Y-m-d H:i') == $day . ' ' . $time) {
        $dates[] = $date;
      }
    }
    sort($dates);
    return $dates;
  }

}
