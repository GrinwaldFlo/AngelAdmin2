<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Content;
use Authorization\IdentityInterface;

/**
 * Content policy
 */
class ContentPolicy
{
  public function canEditor(IdentityInterface $user, Content $resource)
  {
    return $user->Role->Editor;
  }

}


