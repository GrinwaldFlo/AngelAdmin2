<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Presence;
use Authorization\IdentityInterface;

/**
 * Presence policy
 */
class PresencePolicy
{
  public function canViewAll(IdentityInterface $user, Presence $presence)
  {
    return $user->Role->MemberEditAll;
  }

  // Coaches: members of their teams
  public function canEdit(IdentityInterface $user, Presence $presence)
  {
    return $user->Role->MemberEditAll && TeamScope::canAccessMember($user, $presence->member_id);
  }

  public function canAdmin(IdentityInterface $user, Presence $presence)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, Presence $presence)
  {
    return $user->Role->MemberViewAll && TeamScope::canAccessMember($user, $presence->member_id);
  }

}