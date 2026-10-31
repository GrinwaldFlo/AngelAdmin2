<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\MemberField1;
use Authorization\IdentityInterface;

/**
 * MemberField1 policy
 */
class MemberField1Policy
{
  public function canViewAll(IdentityInterface $user, MemberField1 $memberField1)
  {
    return $user->Role->MemberViewAll;
  }

  // Coaches: members of their teams
  public function canEdit(IdentityInterface $user, MemberField1 $memberField1)
  {
    if ($memberField1->member_id == $user->member_id && $user->Role->MemberEditOwn) {
      return true;
    }
    return $user->Role->MemberEditAll && TeamScope::canAccessMember($user, $memberField1->member_id);
  }

  public function canAdmin(IdentityInterface $user, MemberField1 $memberField1)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, MemberField1 $memberField1)
  {
    if ($user->member_id == $memberField1->member_id)
    {
      return true;
    }
    return $user->Role->MemberViewAll && TeamScope::canAccessMember($user, $memberField1->member_id);
  }

 

}