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

  public function canEdit(IdentityInterface $user, Presence $presence)
  {
    return $user->Role->MemberEditAll;
  }

  public function canAdmin(IdentityInterface $user, Presence $presence)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, Presence $presence)
  {
    return $user->Role->MemberViewAll;
  }

}