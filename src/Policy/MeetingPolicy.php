<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Meeting;
use Authorization\IdentityInterface;

/**
 * Meeting policy
 */
class MeetingPolicy
{
  public function canViewAll(IdentityInterface $user, Meeting $meeting)
  {
    return $user->Role->MemberViewAll;
  }

  public function canEdit(IdentityInterface $user, Meeting $meeting)
  {
    return $user->Role->MemberEditAll;
  }

  public function canAdmin(IdentityInterface $user, Meeting $meeting)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, Meeting $meeting)
  {
    return $user->Role->MemberViewAll;
  }

    public function canJoin(IdentityInterface $user, Meeting $meeting)
  {
    return true;
  }
}