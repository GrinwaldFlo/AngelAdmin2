<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;

/**
 * User Entity
 *
 * @property int $id
 * @property string|null $username
 * @property string|null $password
 * @property int|null $role_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property int $member_id
 * @property string $pass_key
 * @property string $tokenhash
 * @property \Cake\I18n\DateTime|null $lastLogin
 *
 * @property \App\Model\Entity\Role $role
 * @property \App\Model\Entity\Member $member
 */
class User extends Entity
{
  /**
   * @var mixed|null
   */
  protected $_hasAllowsNull;

  protected $roleCache = null;

  /**
   * Fields that can be mass assigned using newEntity() or patchEntity().
   *
   * Note that when '*' is set to true, this allows all unspecified fields to
   * be mass assigned. For security purposes, it is advised to set '*' to false
   * (or remove it), and explicitly make individual fields accessible as needed.
   *
   * @var array
   */
  protected array $_accessible = [
    'username' => true,
    'password' => true,
    'role_id' => true,
    'created' => true,
    'modified' => true,
    'member_id' => true,
    'pass_key' => true,
    'tokenhash' => true,
    'role' => true,
    'member' => true,
    'last_login' => true,
  ];
  /**
   * Fields that are excluded from JSON versions of the entity.
   *
   * @var array
   */
  protected array $_hidden = [
    'password',
  ];
  protected function _setPassword(string $password): ?string
  {
    if (strlen($password) > 0) {
      return (new DefaultPasswordHasher())->hash($password);
    }
  }

  protected function _getRole()
  {
    if ($this->roleCache != null) {
      return $this->roleCache;
    }
    $roles = TableRegistry::getTableLocator()->get('Roles');
    $this->roleCache = $roles->findById($this->role_id)->firstOrFail();
    return $this->roleCache;
  }

}