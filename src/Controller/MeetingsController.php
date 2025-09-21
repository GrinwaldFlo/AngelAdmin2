<?php
declare(strict_types=1);
namespace App\Controller;
use Cake\ORM\TableRegistry;

/**
 * Meetings Controller
 *
 * @property \App\Model\Table\MeetingsTable $Meetings
 *
 * @method \App\Model\Entity\Meeting[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class MeetingsController extends AppController
{
  public array $paginate = [
      'limit' => 500,
      'maxLimit' => 1000,
      'order' => ['Meetings.meeting_date' => 'asc']
  ];
  /**
   * Index method
   *
   * @return \Cake\Http\Response|null|void Renders view
   */
  public function index(int|null $teamId = null,int|null  $meetingFilter = null)
  {
    $this->Authorization->authorize($this->Meetings->newEmptyEntity(), 'viewall');
    $teamId = $this->getPrefSession('teamId', $teamId, 0);
    $meetingFilter = $this->getPrefSession('meetingFilter', $meetingFilter, 1);

    if ($meetingFilter == 1)
    {
      $this->paginate['order'] = ['Meetings.meeting_date' => 'desc'];
    }
    if ($meetingFilter == 3)
    {
      $this->paginate['order'] = ['Meetings.meeting_date' => 'asc'];
    }

    $meetings = $this->paginate($this->Meetings->find('Meetings',
    teamId: $teamId,
    meetingFilter: $meetingFilter)->contain('Teams'));

    $teams = $this->getTeamsActiv();

    $this->set(compact('meetings', 'teamId', 'meetingFilter', 'teams'));
  }

  /**
   * View method
   *
   * @param string|null $id Meeting id.
   * @return \Cake\Http\Response|null|void Renders view
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function view(int|null $id = null)
  {
    $this->Authorization->authorize($this->Meetings->newEmptyEntity(), 'viewall');

    $meeting = $this->Meetings->findById($id)->contain([
          'Teams',
          'Presences',
          'Presences.Members',
      ])->first(); // => ['sort' => ['Members.first_name' => 'ASC']]

    $membersFull = $this->getMembers($meeting->team_id, 1);
    $members = Array();

    foreach ($meeting->presences as $presence)
    {
      $members[$presence->member_id] = ['id' => $presence->member_id, 'state' => $presence->state, 'name' => $presence->member->FullNameShort];
    }

    foreach ($membersFull as $member)
    {
      if (empty($members[$member->id]))
      {
        $members[$member->id] = ['id' => $member->id, 'state' => -1, 'name' => $member->FullNameShort];
      }
    }

    $this->set(compact('meeting', 'members'));
  }

  public function add()
  {
    $meeting = $this->Meetings->newEmptyEntity();
    $this->Authorization->authorize($meeting, 'edit');

    if ($this->request->is('post'))
    {
      $meeting = $this->Meetings->patchEntity($meeting, $this->request->getData());

      if (empty($meeting->meeting_date) && !empty($this->request->getData()['meeting_date']))
      {
        $meeting->meeting_date = $this->request->getData()['meeting_date'];
      }

      if (!empty($meeting->meeting_date) && $this->Meetings->save($meeting))
      {
        $this->Flash->success(__('The meeting has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The meeting could not be saved.'));
    }
    $teams = $this->getTeamsActiv();
    $this->set(compact('meeting', 'teams'));
  }

  public function addMultiple()
  {
    $meeting = $this->Meetings->newEmptyEntity();
    $this->Authorization->authorize($meeting, 'edit');

    if ($this->request->is('post'))
    {
      $result = $this->request->getData();
//debug($this->request->getData());
      $meetingCreated = 0;
      foreach ($result['Date'] as $key => $value)
      {
        if ($value == 1)
        {
          $meeting = $this->Meetings->newEmptyEntity();
          $meeting->big_event = $result['big_event'];
          $meeting->team_id = $result['team_id'];
          $meeting->name = $result['name'];
          $meeting->url = $result['url'];

          $tmpDate = new \DateTime();
          $tmpDate->setTimestamp($key);

          $tmpDate = new \DateTime($tmpDate->format('Y-m-d') . 'T' . $result['meeting_date']);
          $meeting->meeting_date = new \Cake\I18n\DateTime($tmpDate->format('Y-m-d H:i:s'));
          if (!empty($meeting->meeting_date) && $this->Meetings->save($meeting))
          {
            $meetingCreated++;
          }
          else
          {
            $this->Flash->error(__('The meeting could not be saved.'));
          }
        }
      }

      if($meetingCreated > 0)
      {
        $this->Flash->success(__("{0} meeting has been saved.", $meetingCreated));
      }
      else
      {
        $this->Flash->error(__('No meeting has been saved.'));
      }
      return $this->redirect(['action' => 'index']);
    }
    $teams = $this->getTeamsActiv();

    $this->set(compact('meeting', 'teams'));
  }

  /**
   * Edit method
   *
   * @param string|null $id Meeting id.
   * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function edit(int|null $id = null)
  {
    $meeting = $this->Meetings->get($id, contain: []);
    $this->Authorization->authorize($meeting, 'edit');

    if ($this->request->is(['patch', 'post', 'put']))
    {
      $meeting = $this->Meetings->patchEntity($meeting, $this->request->getData());
      if (empty($meeting->meeting_date) && !empty($this->request->getData()['meeting_date']))
      {
        $meeting->meeting_date = $this->request->getData()['meeting_date'];
      }

      if (!empty($meeting->meeting_date) && $this->Meetings->save($meeting))
      {
        $this->Flash->success(__('The meeting has been saved.'));

        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('The meeting could not be saved.'));
    }
    $teams = $this->getTeamsActiv();
    $this->set(compact('meeting', 'teams'));
  }

  /**
   * Delete method
   *
   * @param string|null $id Meeting id.
   * @return \Cake\Http\Response|null|void Redirects to index.
   * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
   */
  public function delete(int|null $id = null)
  {
    $this->request->allowMethod(['post', 'delete']);
    $meeting = $this->Meetings->get($id);
    $this->Authorization->authorize($meeting, 'edit');
    if ($this->Meetings->delete($meeting))
    {
      $this->Flash->success(__('The meeting has been deleted.'));
    }
    else
    {
      $this->Flash->error(__('The meeting could not be deleted.'));
    }

    return $this->redirect(['action' => 'index']);
  }

  public function join(int|null $id = null)
  {
    $this->request->allowMethod(['post', 'delete']);
    $meeting = $this->Meetings->get($id);
    $this->Authorization->authorize($meeting, 'join');
    $presences = TableRegistry::getTableLocator()->get('Presences');

    $presence = $presences->find('all')->where(['member_id' => $this->curMember->id])->where(['meeting_id' => $id])->first();

    if (empty($presence))
    {
      $presence = $presences->newEmptyEntity();
      $presence->meeting_id = $id;
      $presence->member_id = $this->curMember->id;
    }
    $presence->state = 1;

    $presences->save($presence);
    $meeting->CheckPresences(true);

    $this->Flash->success(__('You are now registered'));
    return $this->redirect('/');
  }

}
