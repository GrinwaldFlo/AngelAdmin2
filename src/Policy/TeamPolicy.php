<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Team;
use Authorization\IdentityInterface;

/**
 * Team policy
 * A TeamScoped role (coach) only reaches the teams it coaches
 */
class TeamPolicy
{
  public function canViewAll(IdentityInterface $user, Team $team)
  {
    return $user->Role->MemberViewAll;
  }

  // Add / remove members of the team
  public function canEdit(IdentityInterface $user, Team $team)
  {
    return $user->Role->MemberEditAll && TeamScope::canAccessTeam($user, $team->id);
  }

  public function canAdmin(IdentityInterface $user, Team $team)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, Team $team)
  {
    return $user->Role->MemberViewAll && TeamScope::canAccessTeam($user, $team->id);
  }

  public function canAssignCoaches(IdentityInterface $user, Team $team)
  {
    return TeamScope::canUnscoped($user, 'MemberEditAll');
  }
}
