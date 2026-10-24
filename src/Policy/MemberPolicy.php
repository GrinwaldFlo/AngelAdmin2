<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Member;
use Authorization\IdentityInterface;

/**
 * Member policy
 */
class MemberPolicy
{
  public function canViewAll(?IdentityInterface $user, Member $member)
  {
    return $user !== null && $user->Role->MemberViewAll;
  }

  public function canEdit(?IdentityInterface $user, Member $member)
  {
    if ($user === null) {
      return false;
    }

    return $user->Role->MemberEditAll || ($member->id == $user->member_id && $user->Role->MemberEditOwn);
  }
  
    public function canEditAll(?IdentityInterface $user, Member $member)
  {
    return $user !== null && $user->Role->MemberEditAll;
  }

  // Same roles as the ones seeing the site (city) menu
  public function canSetSite(?IdentityInterface $user, Member $member)
  {
    return $user !== null && ($user->Role->MemberViewAll || $user->Role->BillViewAll);
  }

  public function canAdmin(?IdentityInterface $user, Member $member)
  {
    return $user !== null && $user->Role->Admin;
  }

  public function canView(?IdentityInterface $user, Member $member)
  {
    if ($user === null) {
      return false;
    }

    if ($user->Role->MemberViewAll)
    {
      return true;
    }
    return $user->member_id == $member->id;
  }
}