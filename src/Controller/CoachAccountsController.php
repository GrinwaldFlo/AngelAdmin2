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
        // The policy only checks the member and the site: the account itself is loaded by setIban()
        $this->Authorization->authorize(
            $this->CoachAccounts->newEntity(['member_id' => $memberId, 'site_id' => (int)$siteId], ['accessibleFields' => ['member_id' => true, 'site_id' => true], 'validate' => false]),
            'setOwnIban',
        );

        $account = $this->CoachAccounts->patchIban($memberId, (int)$siteId, (string)$this->request->getData('iban'));
        $this->flashSaved((bool)$this->CoachAccounts->save($account), $account, __('Your IBAN has been saved.'), __('The IBAN could not be saved.'));

        return $this->redirect(['controller' => 'Members', 'action' => 'view', $memberId, '#' => 'coach-balance']);
    }
}
