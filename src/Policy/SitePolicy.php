<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Site;
use Authorization\IdentityInterface;

/**
 * Site policy
 */
class SitePolicy
{
  public function canAdmin(IdentityInterface $user, Site $site)
  {
    return $user->Role->Admin;
  }
}