<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\CoachPayment;
use Authorization\IdentityInterface;

/**
 * Coach payment module: settings, reporting and payments are for the admins only
 */
class CoachPaymentPolicy
{
    public function canAdmin(IdentityInterface $user, CoachPayment $payment): bool
    {
        return (bool)$user->Role->Admin;
    }
}
