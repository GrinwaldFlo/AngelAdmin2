<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Mailing;
use Authorization\IdentityInterface;
use Cake\ORM\TableRegistry;

/**
 * Mailing policy
 * A TeamScoped role (coach) only sends to the teams it coaches: the send page shows the email addresses
 */
class MailingPolicy
{
    public function canSend(IdentityInterface $user, Mailing $mailing)
    {
        return $user->Role->Admin || $user->Role->MemberEditAll;
    }

    /**
     * Send or delete an existing mailing: every team of the mailing must be accessible
     */
    public function canManage(IdentityInterface $user, Mailing $mailing)
    {
        if (!$this->canSend($user, $mailing)) {
            return false;
        }
        if (!TeamScope::isScoped($user)) {
            return true;
        }

        $teamIds = TableRegistry::getTableLocator()->get('Mailings')->teamIdsOf($mailing);

        return TeamScope::canAccessAllTeams($user, $teamIds);
    }
}
