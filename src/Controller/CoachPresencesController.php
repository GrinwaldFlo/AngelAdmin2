<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;

/**
 * Coach presence module: presence type of the coaches, set from the attendance page of a meeting
 *
 * @property \App\Model\Table\CoachPresencesTable $CoachPresences
 */
class CoachPresencesController extends AppController
{
    /**
     * Set the presence type of a coach (called from the attendance page)
     * POST data: member_id, type_id (empty = not declared)
     * @return \Cake\Http\Response JSON {saved, error}
     */
    public function setType(int|null $meetingId = null)
    {
        $this->request->allowMethod(['post']);
        $meeting = $this->fetchTable('Meetings')->get($meetingId);
        $this->Authorization->authorize($meeting, 'setPresence');

        $memberId = filter_var($this->request->getData('member_id'), FILTER_VALIDATE_INT);
        $type = $this->request->getData('type_id');
        $typeId = $type === null || $type === '' ? null : filter_var($type, FILTER_VALIDATE_INT);
        if ($memberId === false || $typeId === false) {
            throw new BadRequestException(__('Invalid presence'));
        }

        $error = $this->CoachPresences->setType($meeting, $memberId, $typeId);

        return $this->response
            ->withStatus($error === null ? 200 : 422)
            ->withType('application/json')
            ->withStringBody((string)json_encode(['saved' => $error === null, 'error' => $error]));
    }
}
