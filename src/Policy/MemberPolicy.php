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
  public function canViewAll(IdentityInterface $user, Member $member)
  {
    return $user->Role->MemberViewAll;
  }

  public function canEdit(IdentityInterface $user, Member $member)
  {
    return $user->Role->MemberEditAll || ($member->id == $user->member_id && $user->Role->MemberEditOwn);
  }
  
    public function canEditAll(IdentityInterface $user, Member $member)
  {
    return $user->Role->MemberEditAll;
  }

  public function canAdmin(IdentityInterface $user, Member $member)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, Member $member)
  {
    if ($user->Role->MemberViewAll)
    {
      return true;
    }
    return $user->member_id == $member->id;
  }
}