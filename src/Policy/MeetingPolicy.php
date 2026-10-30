<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Meeting;
use Authorization\IdentityInterface;

/**
 * Meeting policy
 * A TeamScoped role (coach) only reaches the meetings of its teams
 */
class MeetingPolicy
{
  public function canViewAll(IdentityInterface $user, Meeting $meeting)
  {
    return $user->Role->MemberViewAll;
  }

  // A new meeting without team yet is allowed, it is checked again once its team is set (the team is required by the validation)
  public function canEdit(IdentityInterface $user, Meeting $meeting)
  {
    return $user->Role->MemberEditAll && (($meeting->isNew() && $meeting->team_id === null) || TeamScope::canAccessTeam($user, $meeting->team_id));
  }

  public function canAdmin(IdentityInterface $user, Meeting $meeting)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, Meeting $meeting)
  {
    return $user->Role->MemberViewAll && TeamScope::canAccessTeam($user, $meeting->team_id);
  }

  /**
   * Taking attendance is allowed to everyone who can open the attendance page
   */
  public function canSetPresence(IdentityInterface $user, Meeting $meeting)
  {
    return $this->canView($user, $meeting);
  }

  public function canJoin(IdentityInterface $user, Meeting $meeting)
  {
    return true;
  }
}