<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Team;
use Authorization\IdentityInterface;

/**
 * Team policy
 */
class TeamPolicy
{
  public function canViewAll(IdentityInterface $user, Team $team)
  {
    return $user->Role->MemberViewAll;
  }

  public function canEdit(IdentityInterface $user, Team $team)
  {
    return $user->Role->MemberEditAll;
  }

  public function canAdmin(IdentityInterface $user, Team $team)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, Team $team)
  {
    return $user->Role->MemberViewAll;
  }

}