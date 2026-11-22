<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\CoachPresenceLimitsTable;
use Cake\I18n\Number;
use RuntimeException;

/**
 * Coach payment module, admin pages of one site: presence types, IBAN of the coaches, balances and payments
 *
 * @property \App\Model\Table\CoachPaymentsTable $CoachPayments
 */
class CoachPaymentsController extends AppController
{
    use EntityFlashTrait;

    /**
     * Admin only, returns the site of the pages (always one site, never "all sites")
     */
    private function adminSiteId(): int
    {
        $this->Authorization->authorize($this->CoachPayments->newEmptyEntity(), 'admin');

        return $this->getSiteId(true);
    }

    /**
     * Reporting: one section per coach of the site with statistics, open presences, QR code and payments
     */
    public function index()
    {
        $siteId = $this->adminSiteId();
        $site = PrefResult::getSite($siteId);
        $presences = $this->fetchTable('CoachPresences');
        $accountsTable = $this->fetchTable('CoachAccounts');

        $coaches = $accountsTable->coachesOfSite($siteId);
        $accounts = $accountsTable->accountsOfSite($siteId);
        $teamNames = $accountsTable->coachedTeamNames($siteId);
        $types = $this->fetchTable('CoachPresenceTypes')->find('ofSite', siteId: $siteId, activeOnly: false)->all()->indexBy('id')->toArray();
        $balances = $presences->balances($siteId);
        $statistics = $presences->statistics($siteId, $this->config['dateSeasonStart']);
        $payments = $this->CoachPayments->paymentsOfSite($siteId);

        // QR code of the due balance, or what is missing to build it
        $qrCodes = [];
        $qrMissing = [];
        foreach ($balances as $memberId => $balance) {
            if ($balance['dueTotal'] <= 0 || !isset($coaches[$memberId])) {
                continue;
            }
            $qrMissing[$memberId] = $this->CoachPayments->qrMissing($coaches[$memberId], $accounts[$memberId] ?? null);
            if ($qrMissing[$memberId] === []) {
                try {
                    $information = __('Coach {0} - {1}', $this->config['year'], $coaches[$memberId]->full_name);
                    $qrCodes[$memberId] = $this->CoachPayments->qrCode($coaches[$memberId], $accounts[$memberId], $site, $balance['dueTotal'], $information);
                } catch (RuntimeException $e) {
                    $qrMissing[$memberId][] = $e->getMessage();
                }
            }
        }

        $this->set(compact('site', 'coaches', 'accounts', 'teamNames', 'types', 'balances', 'statistics', 'payments', 'qrCodes', 'qrMissing'));
    }

    /**
     * Settings: presence types, limits of the types per team and IBAN of the coaches of the site
     */
    public function settings()
    {
        $siteId = $this->adminSiteId();
        $accountsTable = $this->fetchTable('CoachAccounts');

        $site = PrefResult::getSite($siteId);
        $types = $this->fetchTable('CoachPresenceTypes')->find('ofSite', siteId: $siteId, activeOnly: false)->all();
        $newType = $this->fetchTable('CoachPresenceTypes')->newEmptyEntity();
        $coaches = $accountsTable->coachesOfSite($siteId);
        $accounts = $accountsTable->accountsOfSite($siteId);
        $teamNames = $accountsTable->coachedTeamNames($siteId);
        $teams = $this->getTeamsActiv($siteId, false);
        $limits = $this->fetchTable('CoachPresenceLimits')->limitsOfTeams(array_keys($teams));
        $activeTypes = $types->filter(fn ($type) => $type->active)->toList();

        $this->set(compact('site', 'types', 'activeTypes', 'newType', 'coaches', 'accounts', 'teamNames', 'teams', 'limits'));
    }

    /**
     * Save the maximum number of coaches per presence type at one meeting, for the active teams of the site
     * POST data: limits[team id][type id] = max, empty = no limit
     */
    public function saveLimits()
    {
        $this->request->allowMethod(['post']);
        $siteId = $this->adminSiteId();
        $teamIds = array_keys($this->getTeamsActiv($siteId, false));
        $typeIds = $this->fetchTable('CoachPresenceTypes')->find('ofSite', siteId: $siteId)->all()->extract('id')->toList();
        $data = $this->request->getData('limits');

        if ($this->fetchTable('CoachPresenceLimits')->saveLimits(is_array($data) ? $data : [], $teamIds, $typeIds)) {
            $this->Flash->success(__('The limits have been saved.'));
        } else {
            $this->Flash->error(__('The limits could not be saved: each limit must be a number from 0 to {0}.', CoachPresenceLimitsTable::MAX));
        }

        return $this->redirect(['action' => 'settings', '#' => 'limits']);
    }

    public function addType()
    {
        $this->request->allowMethod(['post']);
        $siteId = $this->adminSiteId();
        $typesTable = $this->fetchTable('CoachPresenceTypes');
        $type = $typesTable->newForSite($siteId, $this->request->getData());
        $typesTable->save($type);
        $this->flashSaved($type, __('The presence type has been saved.'), __('The presence type could not be saved.'));

        return $this->redirect(['action' => 'settings']);
    }

    public function editType(int|null $id = null)
    {
        $siteId = $this->adminSiteId();
        $typesTable = $this->fetchTable('CoachPresenceTypes');
        $type = $typesTable->get($id);
        if ($type->site_id != $siteId) {
            $this->Flash->error(__('This presence type belongs to another site.'));

            return $this->redirect(['action' => 'settings']);
        }
        if ($this->request->is(['post', 'put', 'patch'])) {
            $type = $typesTable->patchEntity($type, $this->request->getData());
            $typesTable->save($type);
            if ($this->flashSaved($type, __('The presence type has been saved.'), __('The presence type could not be saved.'))) {
                return $this->redirect(['action' => 'settings']);
            }
        }

        $this->set(compact('type'));
    }

    public function deleteType(int|null $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $siteId = $this->adminSiteId();
        $typesTable = $this->fetchTable('CoachPresenceTypes');
        $type = $typesTable->get($id);
        if ($type->site_id == $siteId && $typesTable->delete($type)) {
            $this->Flash->success(__('The presence type has been deleted.'));
        } else {
            $errors = $type->getError('label');
            $this->Flash->error(__('The presence type could not be deleted.') . ($errors ? ' ' . reset($errors) : ''));
        }

        return $this->redirect(['action' => 'settings']);
    }

    /**
     * Set or replace the IBAN of a coach of the site
     */
    public function setIban(int|null $memberId = null)
    {
        $this->request->allowMethod(['post']);
        $siteId = $this->adminSiteId();
        $accountsTable = $this->fetchTable('CoachAccounts');
        if (!isset($accountsTable->coachesOfSite($siteId)[(int)$memberId])) {
            $this->Flash->error(__('This member is not a coach of this site.'));

            return $this->redirect(['action' => 'settings']);
        }
        $account = $accountsTable->setIban((int)$memberId, $siteId, (string)$this->request->getData('iban'));
        $this->flashSaved($account, __('The IBAN has been saved.'), __('The IBAN could not be saved.'));

        return $this->redirect(['action' => 'settings']);
    }

    public function removeIban(int|null $memberId = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $siteId = $this->adminSiteId();
        if ($this->fetchTable('CoachAccounts')->removeIban((int)$memberId, $siteId)) {
            $this->Flash->success(__('The IBAN has been removed.'));
        } else {
            $this->Flash->error(__('The IBAN could not be removed.'));
        }

        return $this->redirect(['action' => 'settings']);
    }

    /**
     * Mark the due balance of a coach as paid (after scanning the QR code)
     * POST data: amount = total shown on the page
     */
    public function pay(int|null $memberId = null)
    {
        $this->request->allowMethod(['post']);
        $siteId = $this->adminSiteId();
        $expected = $this->request->getData('amount');
        $result = $this->CoachPayments->payOpen((int)$memberId, $siteId, is_numeric($expected) ? (float)$expected : null);
        if (is_string($result)) {
            $this->Flash->error($result);
        } else {
            $this->Flash->success(__('The payment of {0} has been saved.', Number::currency((float)$result->amount, 'CHF')));
        }

        return $this->redirect(['action' => 'index', '#' => 'coach-' . (int)$memberId]);
    }

    /**
     * Delete a payment: its presences become open again
     */
    public function deletePayment(int|null $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $siteId = $this->adminSiteId();
        $payment = $this->CoachPayments->get($id);
        if ($payment->site_id == $siteId && $this->CoachPayments->delete($payment)) {
            $this->Flash->success(__('The payment has been deleted, its presences are open again.'));
        } else {
            $this->Flash->error(__('The payment could not be deleted.'));
        }

        return $this->redirect(['action' => 'index', '#' => 'coach-' . $payment->member_id]);
    }
}
