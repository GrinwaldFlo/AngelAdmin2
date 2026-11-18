<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Coach payment module: a coach sets their own IBAN from their page (My account)
 *
 * @property \App\Model\Table\CoachAccountsTable $CoachAccounts
 */
class CoachAccountsController extends AppController
{
    use EntityFlashTrait;

    /**
     * Set or replace the IBAN of the current user's member for a site, never for another member
     */
    public function setIban(int|null $siteId = null)
    {
        $this->request->allowMethod(['post']);
        $memberId = (int)$this->curUser?->member_id;
        $account = $this->CoachAccounts->accountOf($memberId, (int)$siteId) ?? $this->CoachAccounts->newEmptyEntity();
        $account->member_id = $memberId;
        $account->site_id = (int)$siteId;
        $this->Authorization->authorize($account, 'setOwnIban');

        $account = $this->CoachAccounts->setIban($memberId, (int)$siteId, (string)$this->request->getData('iban'));
        $this->flashSaved($account, __('Your IBAN has been saved.'), __('The IBAN could not be saved.'));

        return $this->redirect(['controller' => 'Members', 'action' => 'view', $memberId, '#' => 'coach-balance']);
    }
}
