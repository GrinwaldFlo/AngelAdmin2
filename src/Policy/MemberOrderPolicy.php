<?php

declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;

/**
 * Member order policy: the shop managers (see TeamScope::canManageShop())
 */
class MemberOrderPolicy
{
    public function canViewAll(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    public function canView(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    public function canMarkDelivered(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    public function canMarkNotDelivered(IdentityInterface $user): bool
    {
        return $this->canMarkDelivered($user);
    }

    public function canChangeAttribute(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    public function canDelete(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    /**
     * Place an order for another member, from its member page: only the merchandising managers
     */
    public function canOrderFor(IdentityInterface $user): bool
    {
        return (bool)$user->Role->ShopManager;
    }
}
