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

    $this->set(compact('teams'));
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
    $this->Authorization->authorize($this->Teams->newEmptyEntity(), 'viewall');

    $team = $this->Teams->findById($id)->contain([
      'Meetings' => ['sort' => ['Meetings.meeting_date' => 'DESC']],
      //          'Members' => ['sort' => ['Members.first_name' => 'ASC']],
    ])->contain(['Sites'])->first();

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

    $this->set(compact('team', 'membersIn', 'membersOut', 'membersOutTmp'));
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

    $this->Authorization->authorize($team);

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