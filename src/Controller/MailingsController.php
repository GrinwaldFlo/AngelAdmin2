<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Mailings Controller: emails to the members of teams
 * The emails are queued when the mailing is created, then sent by small chunks from the send page
 * so a batch stopped by the SMTP server (e.g. Gmail sending limit) can be continued later
 *
 * @property \App\Model\Table\MailingsTable $Mailings
 * @property \App\Controller\Component\MailComponent $Mail
 */
class MailingsController extends AppController
{
    // Emails sent by each call of the send page
    public const CHUNK_SIZE = 10;

    /**
     * Log of the mailings
     */
    public function index()
    {
        $this->Authorization->authorize($this->Mailings->newEmptyEntity(), 'send');
        $mailings = $this->paginate($this->Mailings->find()->orderBy(['Mailings.created' => 'DESC', 'Mailings.id' => 'DESC']));
        $counts = $this->Mailings->MailingItems->countsByMailing(array_map(fn ($mailing) => $mailing->id, $mailings->toArray()));

        $this->set(compact('mailings', 'counts'));
    }

    /**
     * Write a mailing and queue its emails
     * @param int|null $sourceId mailing whose subject, content and teams are copied
     */
    public function add(int|null $sourceId = null)
    {
        $this->Authorization->authorize($this->Mailings->newEmptyEntity(), 'send');
        $mailing = $this->Mailings->newEmptyEntity();
        $teamIds = [];
        if ($sourceId !== null && !$this->request->is('post')) {
            [$mailing, $teamIds] = $this->Mailings->copy($this->Mailings->get($sourceId));
        }

        if ($this->request->is('post')) {
            $teamIds = array_map('intval', (array)$this->request->getData('team_ids'));
            $mailing = $this->Mailings->newForTeams($this->request->getData(), $teamIds);
            if (!$mailing->hasErrors() && $this->Mailings->save($mailing)) {
                return $this->redirect(['action' => 'send', $mailing->id]);
            }
            $this->Flash->error(__('The mailing could not be saved.'));
        }

        $teams = $this->getTeamsActiv($this->getSiteId());
        $placeholders = $mailing::placeholders();
        $this->set(compact('mailing', 'teams', 'teamIds', 'placeholders'));
    }

    /**
     * Progress of a mailing: the page sends the pending emails, and shows the ones that could not be sent
     */
    public function send(int|null $id = null)
    {
        $this->Authorization->authorize($this->Mailings->newEmptyEntity(), 'send');
        $mailing = $this->Mailings->get($id);
        $counts = $this->Mailings->MailingItems->countsByMailing([$mailing->id])[$mailing->id];
        $items = $this->Mailings->MailingItems->find()
            ->contain(['Members'])
            ->where(['MailingItems.mailing_id' => $mailing->id])
            ->orderBy(['Members.last_name' => 'ASC', 'Members.first_name' => 'ASC'])
            ->all();
        $failedItems = $items->filter(fn ($item) => $item->error !== null);

        $this->set(compact('mailing', 'counts', 'items', 'failedItems'));
    }

    /**
     * Remove a mailing and its queue, only as long as none of its emails has been sent
     */
    public function delete(int|null $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->Authorization->authorize($this->Mailings->newEmptyEntity(), 'send');
        $mailing = $this->Mailings->get($id);

        if ($this->Mailings->MailingItems->countsByMailing([$mailing->id])[$mailing->id]['sent'] > 0) {
            $this->Flash->error(__('Emails of this mailing have already been sent, it cannot be deleted.'));

            return $this->redirect(['action' => 'send', $mailing->id]);
        }
        if ($this->Mailings->delete($mailing)) {
            $this->Flash->success(__('The mailing has been deleted.'));
        } else {
            $this->Flash->error(__('The mailing could not be deleted.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Send the next chunk of pending emails (called by the send page)
     */
    public function sendNext(int|null $id = null)
    {
        $this->request->allowMethod(['post']);
        $this->Authorization->authorize($this->Mailings->newEmptyEntity(), 'send');
        $mailing = $this->Mailings->get($id);

        $result = $this->Mail->sendMailing($mailing, self::CHUNK_SIZE);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($result));
    }
}
