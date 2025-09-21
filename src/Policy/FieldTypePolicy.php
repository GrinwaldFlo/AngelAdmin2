<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\FieldType;
use Authorization\IdentityInterface;

/**
 * FieldType policy
 */
class FieldTypePolicy
{
  public function canAdmin(IdentityInterface $user, FieldType $fieldType)
  {
    return $user->Role->Admin;
  }

  /**
   * Check if $user can create FieldType
   *
   * @param Authorization\IdentityInterface $user The user.
   * @param App\Model\Entity\FieldType $fieldType
   * @return bool
   */
  public function canCreate(IdentityInterface $user, FieldType $fieldType)
  {
    
  }

  /**
   * Check if $user can update FieldType
   *
   * @param Authorization\IdentityInterface $user The user.
   * @param App\Model\Entity\FieldType $fieldType
   * @return bool
   */
  public function canUpdate(IdentityInterface $user, FieldType $fieldType)
  {
    
  }

  /**
   * Check if $user can delete FieldType
   *
   * @param Authorization\IdentityInterface $user The user.
   * @param App\Model\Entity\FieldType $fieldType
   * @return bool
   */
  public function canDelete(IdentityInterface $user, FieldType $fieldType)
  {
    
  }

  /**
   * Check if $user can view FieldType
   *
   * @param Authorization\IdentityInterface $user The user.
   * @param App\Model\Entity\FieldType $fieldType
   * @return bool
   */
  public function canView(IdentityInterface $user, FieldType $fieldType)
  {
    
  }

}