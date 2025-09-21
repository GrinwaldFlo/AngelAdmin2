<?php
declare(strict_types=1);
namespace App\Controller;
use Cake\ORM\TableRegistry;

/**
 * Presences Controller
 *
 * @property \App\Model\Table\PresencesTable $Presences
 *
 * @method \App\Model\Entity\Presence[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PresencesController extends AppController
{
  public function beforeFilter(\Cake\Event\EventInterface $event)
  {
    parent::beforeFilter($event);
    // Configure the login action to not require authentication, preventing
    // the infinite redirect loop issue
    $this->Authentication->addUnauthenticatedActions(['setPresence']);
  }

  public function setPresence(int|null $meetingId = null, int|null $memberId = null, $state = null)
  {
    $this->Authorization->skipAuthorization();

    if ($meetingId == null || $memberId == null || $state == null)
      return;

    $meetings = TableRegistry::getTableLocator()->get('Meetings');
    $meeting = $meetings->findById($meetingId)->firstOrFail();
    $presence = $this->Presences->find('all')->where(['member_id' => $memberId])->where(['meeting_id' => $meetingId])->first();

    if (empty($presence)) {
      $presence = $this->Presences->newEmptyEntity();
      $presence->meeting_id = $meetingId;
      $presence->member_id = $memberId;
    }
    $presence->state = $state;

    $this->Presences->save($presence);
    $meeting->CheckPresences(true);
  }

}