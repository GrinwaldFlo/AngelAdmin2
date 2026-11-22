<?php

declare(strict_types=1);

namespace App\View\Cell;

use App\Controller\PrefResult;
use App\Model\Entity\Member;
use Cake\View\Cell;

/**
 * Coach payment module: balance, payments and IBAN of a coach on their own page (My account)
 * Only shown to the logged user viewing their own member, one card per site where they are a coach
 */
class CoachBalanceCell extends Cell
{
    /** Number of payments shown */
    private const LAST_PAYMENTS = 5;

    public function display(Member $member): void
    {
        $identity = $this->request->getAttribute('identity');
        $cards = [];
        if ($identity !== null && (int)$identity->member_id === (int)$member->id) {
            $presences = $this->fetchTable('CoachPresences');
            $payments = $this->fetchTable('CoachPayments');
            $accounts = $this->fetchTable('CoachAccounts');
            foreach ($accounts->siteIdsOf((int)$member->id) as $siteId) {
                $cards[$siteId] = [
                    'site' => PrefResult::getSite($siteId),
                    'balance' => $presences->balances($siteId, (int)$member->id)[$member->id] ?? null,
                    'payments' => array_slice($payments->paymentsOfSite($siteId, (int)$member->id)[$member->id] ?? [], 0, self::LAST_PAYMENTS),
                    'account' => $accounts->accountOf((int)$member->id, $siteId),
                ];
            }
        }

        $this->set(compact('cards'));
    }
}
