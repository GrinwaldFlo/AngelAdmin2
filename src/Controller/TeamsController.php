<?php
declare(strict_types=1);
namespace App\Controller;

/**
 * Teams Controller
 *
 * @property \App\Model\Table\TeamsTable $Teams
 * @property \App\Model\Table\MembersTable $Members
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 *
 * @method \App\Model\Entity\Team[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TeamsController extends AppController
{
  /**
   * @var \App\Model\Table\MembersTable
   */
  public $Members;
  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index()
  {
    $this->Authorization->authorize($this->Teams->newEmptyEntity(), 'viewall');
    $teams = $this->Teams->find('all')->orderBy(['Name' => 'ASC'])->contain(['Sites']);
    $siteId = $this->getSiteId();
    if ($siteId > 0) {
      $teams->where(['Teams.site_id =' => $siteId]);
    }
    // Coaches: only their teams
    $coachedTeamIds = $this->getCoachedTeamIds();
    if ($coachedTeamIds !== null) {
      $teams->where(['Teams.id IN' => $coachedTeamIds ?: [0]]);
    }
    $canAssignCoaches = $this->Authorization->can($this->Teams->newEmptyEntity(), 'assignCoaches');

    $this->set(compact('teams', 'canAssignCoaches'));
  }

  /**
   * View method
   *
   * @param string|null $id Team id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view(int|null $id = null)
  {
    $team = $this->Teams->findById($id)->contain([
      'Meetings' => ['sort' => ['Meetings.meeting_date' => 'DESC']],
      //          'Members' => ['sort' => ['Members.first_name' => 'ASC']],
    ])->contain(['Sites'])->firstOrFail();
    $this->Authorization->authorize($team, 'view');

    $this->Members = $this->fetchTable('Members');
    $membersIn = $this->Members->find('all', array('order' => array('first_name' => 'asc')))
      ->where(['Members.active =' => 1])
      ->innerJoinWith('Teams')->where(['Teams.id IN' => $id]);

    $membersOutTmp = $this->Members->find('all', array('order' => array('first_name' => 'asc')))
      ->contain('Teams')
      ->where(['Members.active =' => 1]);

    $membersOut = array();
    foreach ($membersOutTmp as $item) {
      $isInTeam = false;
      foreach ($item->teams as $grp) {
        if ($grp->id == $id) {
          $isInTeam = true;
          continue;
        }
      }
      if (!$isInTeam) {
        array_push($membersOut, $item);
      }
    }

    // Coaches can add any active member to their team, but only open the members of their teams
    $coachedTeamIds = $this->getCoachedTeamIds();
    $visibleMemberIds = [];
    foreach ($membersOut as $member) {
      if ($coachedTeamIds === null || array_intersect($member->TeamIds, $coachedTeamIds)) {
        $visibleMemberIds[$member->id] = true;
      }
    }
    $teamCoaches = $this->fetchTable('TeamsCoaches')->coachesOf($team->id);

    $this->set(compact('team', 'membersIn', 'membersOut', 'membersOutTmp', 'visibleMemberIds', 'teamCoaches'));
  }

  /**
   * Add method
   *
   * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
   */
  public function add()
  {
    $this->Authorization->authorize($this->Teams->newEmptyEntity(), 'admin');

    $sites = $this->Teams->Sites->find('list', [
      'valueField' => function ($site) {
        return $site->city;
      }
    ]);

    $team = $this->Teams->newEmptyEntity();
    if ($this->request->is('post')) {
      $team = $this->Teams->patchEntity($team, $this->request->getData());
      if ($this->Teams->save($team)) {
        $this->Flash->success(__('The team has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The team could not be saved.'));
    }

    $this->set(compact('team', 'sites'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Team id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit(int|null $id = null)
  {
    $team = $this->Teams->get($id, contain: ['Sites']);

    $this->Authorization->authorize($team, 'admin');

    $sites = $this->Teams->Sites->find('list', [
      'valueField' => function ($site) {
        return $site->city;
      }
    ]);

    $this->set(compact('team', 'sites'));

    if ($this->request->is(['patch', 'post', 'put'])) {
      $team = $this->Teams->patchEntity($team, $this->request->getData());
      if ($this->Teams->save($team)) {
        $this->Flash->success(__('The team has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The team could not be saved.'));
    }
  }

  /**
   * Coaches and help-coaches of the active teams (of the selected site)
   */
  public function coaches()
  {
    $this->Authorization->authorize($this->Teams->newEmptyEntity(), 'assignCoaches');
    $teamsCoaches = $this->fetchTable('TeamsCoaches');
    $teams = $this->getTeamsActiv($this->getSiteId(), scoped: false);
    $coaches = $teamsCoaches->rowsByTeam(array_keys($teams));
    $candidates = $teamsCoaches->candidates();

    $this->set(compact('teams', 'coaches', 'candidates'));
  }

  /**
   * Assign a member (having a user account) as coach or help-coach of an active team
   * POST data: team_id, member_id, type
   */
  public function addCoach()
  {
    $this->request->allowMethod(['post']);
    $this->Authorization->authorize($this->Teams->newEmptyEntity(), 'assignCoaches');
    $teamsCoaches = $this->fetchTable('TeamsCoaches');
    $teamId = (int)$this->request->getData('team_id');
    $memberId = (int)$this->request->getData('member_id');
    $teams = $this->getTeamsActiv(scoped: false);
    $candidates = $teamsCoaches->candidates();

    if (!isset($teams[$teamId]) || !isset($candidates[$memberId])) {
      $this->Flash->error(__('The coach could not be added.'));
    } else {
      $row = $teamsCoaches->addCoach($teamId, $memberId, (int)$this->request->getData('type'));
      if (!$row->hasErrors()) {
        $this->Flash->success(__('{0} is now coach of {1}.', $candidates[$memberId], $teams[$teamId]));
      } elseif ($row->getError('member_id')) {
        $this->Flash->error(__('{0} is already a coach of {1}.', $candidates[$memberId], $teams[$teamId]));
      } else {
        $this->Flash->error(__('The coach could not be added.'));
      }
    }

    return $this->redirect(['action' => 'coaches', '#' => 'team-' . $teamId]);
  }

  /**
   * Remove a coach or help-coach from a team
   * @param int|null $id teams_coaches id
   */
  public function removeCoach(int|null $id = null)
  {
    $this->request->allowMethod(['post', 'delete']);
    $this->Authorization->authorize($this->Teams->newEmptyEntity(), 'assignCoaches');
    $teamsCoaches = $this->fetchTable('TeamsCoaches');
    $row = $teamsCoaches->get($id, contain: ['Members', 'Teams']);

    if ($teamsCoaches->delete($row)) {
      $this->Flash->success(__('{0} is no longer coach of {1}.', $row->member->fullName, $row->team->name));
    } else {
      $this->Flash->error(__('The coach could not be removed.'));
    }

    return $this->redirect(['action' => 'coaches', '#' => 'team-' . $row->team_id]);
  }

  public function addMember(int|null $teamId = null, int|null $memberId = null)
  {
    $team = $this->Teams->get($teamId, contain: ['Sites']);

    $this->Authorization->authorize($team, 'edit');
    $this->Members = $this->fetchTable('Members');

    $member = $this->Members->get($memberId, [
      'contain' => ['Teams']
    ]);

    $this->Members->Teams->link($member, [$team]);
    return $this->redirect(['action' => 'view', $teamId]);
  }

  public function removeMember(int|null $teamId = null, int|null $memberId = null)
  {
    $team = $this->Teams->get($teamId, contain: ['Sites']);

    $this->Authorization->authorize($team, 'edit');
    $this->Members = $this->fetchTable('Members');

    $member = $this->Members->get($memberId, [
      'contain' => ['Teams']
    ]);

    $this->Members->Teams->unlink($member, [$team]);
    return $this->redirect(['action' => 'view', $teamId]);
  }

}