<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Configuration;
use Authorization\IdentityInterface;

/**
 * Configuration policy
 */
class ConfigurationPolicy
{
  public function canAdmin(IdentityInterface $user, Configuration $configuration)
  {
    return $user->Role->Admin;
  }

}