<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\BillTemplate;
use Authorization\IdentityInterface;

/**
 * BillTemplate policy
 */
class BillTemplatePolicy
{

  
  public function canAdmin(IdentityInterface $user, BillTemplate $billTemplate)
  {
    return $user->Role->Admin;
  }

}