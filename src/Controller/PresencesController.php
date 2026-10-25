<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Entity\Presence;
use Cake\Http\Exception\BadRequestException;

/**
 * Presences Controller
 *
 * @property \App\Model\Table\PresencesTable $Presences
 *
 * @method \App\Model\Entity\Presence[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PresencesController extends AppController
{
  /**
   * Set the state of one or several members for a meeting (called from the attendance page)
   * POST data: member_id (int or int[]), state (Presence constant)
   * @return \Cake\Http\Response JSON with the new counters of the meeting
   */
  public function setPresence(int|null $meetingId = null)
  {
    $this->request->allowMethod(['post']);
    $meeting = $this->Presences->Meetings->get($meetingId);
    $this->Authorization->authorize($meeting, 'setPresence');

    $state = filter_var($this->request->getData('state'), FILTER_VALIDATE_INT);
    $memberIds = array_values(array_filter(array_map('intval', (array)$this->request->getData('member_id'))));
    if ($state === false || !Presence::isValidState($state) || empty($memberIds)) {
      throw new BadRequestException(__('Invalid presence'));
    }

    $saved = $this->Presences->setState($meeting, $memberIds, $state);

    return $this->response
      ->withStatus($saved ? 200 : 422)
      ->withType('application/json')
      ->withStringBody(json_encode(['saved' => $saved, 'counts' => $meeting->counts()]));
  }

}
