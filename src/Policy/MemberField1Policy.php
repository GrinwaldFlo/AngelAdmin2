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

  public function canEdit(IdentityInterface $user, MemberField1 $memberField1)
  {
    return $user->Role->MemberEditAll || ($memberField1->member_id == $user->member_id && $user->Role->MemberEditOwn);
  }

  public function canAdmin(IdentityInterface $user, MemberField1 $memberField1)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, MemberField1 $memberField1)
  {
    if ($user->Role->MemberViewAll)
    {
      return true;
    }
    return $user->member_id == $memberField1->member_id;
  }

 

}