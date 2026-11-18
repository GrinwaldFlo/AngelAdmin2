<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\CoachAccount;
use Authorization\IdentityInterface;
use Cake\ORM\TableRegistry;

/**
 * IBAN of the coaches
 */
class CoachAccountPolicy
{
    /**
     * A coach sets the IBAN of their own member, only for a site where they are a coach
     */
    public function canSetOwnIban(IdentityInterface $user, CoachAccount $account): bool
    {
        $memberId = (int)$user->member_id;

        return $memberId > 0
            && (int)$account->member_id === $memberId
            && in_array((int)$account->site_id, TableRegistry::getTableLocator()->get('CoachAccounts')->siteIdsOf($memberId), true);
    }
}
