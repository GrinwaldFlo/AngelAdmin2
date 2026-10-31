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
        return TeamScope::canUnscoped($user, 'MemberEditAll');
    }

    public function canEdit(IdentityInterface $user)
    {
        return TeamScope::canUnscoped($user, 'MemberEditAll');
    }

    public function canEditAll(IdentityInterface $user)
    {
        return TeamScope::canUnscoped($user, 'MemberEditAll');
    }

    public function canAdmin(IdentityInterface $user)
    {
        return $user->Role->Admin;
    }

    public function canView(IdentityInterface $user)
    {
        return TeamScope::canUnscoped($user, 'MemberEditAll');
    }

    public function canMarkDelivered(IdentityInterface $user)
    {
        return TeamScope::canUnscoped($user, 'MemberEditAll');
    }

    public function canMarkNotDelivered(IdentityInterface $user)
    {
        return $this->canMarkDelivered($user);
    }

    public function canDelete(IdentityInterface $user)
    {
        return TeamScope::canUnscoped($user, 'MemberEditAll');
    }


}
