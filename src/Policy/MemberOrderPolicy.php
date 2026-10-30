<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Member;
use Authorization\IdentityInterface;

/**
 * Member policy
 */
class MemberOrderPolicy
{
    public function canViewAll(IdentityInterface $user)
    {
        return $user->Role->MemberEditAll && !TeamScope::isScoped($user);
    }

    public function canEdit(IdentityInterface $user)
    {
        return $user->Role->MemberEditAll && !TeamScope::isScoped($user);
    }

    public function canEditAll(IdentityInterface $user)
    {
        return $user->Role->MemberEditAll && !TeamScope::isScoped($user);
    }

    public function canAdmin(IdentityInterface $user)
    {
        return $user->Role->Admin;
    }

    public function canView(IdentityInterface $user)
    {
        return $user->Role->MemberEditAll && !TeamScope::isScoped($user);
    }

    public function canMarkDelivered(IdentityInterface $user)
    {
        return $user->Role->MemberEditAll && !TeamScope::isScoped($user);
    }

    public function canDelete(IdentityInterface $user)
    {
        return $user->Role->MemberEditAll && !TeamScope::isScoped($user);
    }


}
