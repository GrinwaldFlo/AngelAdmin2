<?php

declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;

/**
 * Shop item policy: the shop managers (see TeamScope::canManageShop())
 */
class ShopItemPolicy
{
    public function canViewAll(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    public function canView(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    public function canEdit(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    public function canDelete(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }

    /**
     * Stock of the items (orders summary page)
     */
    public function canManageStock(IdentityInterface $user): bool
    {
        return TeamScope::canManageShop($user);
    }
}
