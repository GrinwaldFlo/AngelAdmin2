<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Bill;
use Authorization\IdentityInterface;
/**
 * Bill policy
 */
class BillPolicy
{
    public function canViewAll(IdentityInterface $user, Bill $bill)
  {
    return $user->Role->BillViewAll;
  }

  public function canEdit(IdentityInterface $user, Bill $bill)
  {
    return $user->Role->BillEditAll;
  }

  public function canAdmin(IdentityInterface $user, Bill $bill)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, Bill $bill)
  {
    if ($user->member_id == $bill->member_id)
    {
      return true;
    }
    return $user->Role->BillViewAll && TeamScope::canAccessMember($user, $bill->member_id);
  }
}