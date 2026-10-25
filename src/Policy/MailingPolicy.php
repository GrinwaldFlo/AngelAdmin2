<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Mailing;
use Authorization\IdentityInterface;

/**
 * Mailing policy
 */
class MailingPolicy
{
  /**
   * Emails to teams are only for the admins for now (could be opened to the coaches of the teams later)
   */
    public function canSend(IdentityInterface $user, Mailing $mailing)
    {
        return $user->Role->Admin;
    }
}
