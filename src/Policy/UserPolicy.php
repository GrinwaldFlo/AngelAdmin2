<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\User;
use Authorization\IdentityInterface;

/**
 * User policy
 */
class UserPolicy
{
  public function canViewAll(IdentityInterface $user, User $resource)
  {
    return $user->Role->Admin;
  }

  public function canEdit(IdentityInterface $user, User $resource)
  {
    return $user->Role->Admin || ($resource->id == $user->id && $user->Role->MemberEditOwn);
  }

  public function canAdmin(IdentityInterface $user, User $resource)
  {
    return $user->Role->Admin;
  }

  public function canView(IdentityInterface $user, User $resource)
  {
    if ($user->Role->Admin)
    {
      return true;
    }
    return $user->id == $resource->id;
  }
}


