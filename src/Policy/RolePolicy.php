<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Role;
use Authorization\IdentityInterface;

/**
 * Role policy
 */
class RolePolicy
{
  public function canAdmin(IdentityInterface $user, Role $resource)
  {
    return $user->Role->Admin;
  }

}