<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * Team Entity
 *
 * @property int $id
 * @property string $name
 * @property int $membership_fee
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property bool $active
 * @property string|null $description
 * @property int $site_id
 *
 * @property \App\Model\Entity\Meeting[] $meetings
 * @property \App\Model\Entity\Member[] $members
 * @property \App\Model\Entity\Site $site
 */
class Team extends Entity
{
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
      'name' => true,
      'membership_fee' => true,
      'created' => true,
      'modified' => true,
      'active' => true,
      'description' => true,
      'site_id' => true,
      'meetings' => true,
      'members' => true,
      'site' => true,
  ];
  protected function _getNbMembers()
  {
    if (!empty($this->cacheNbMembers))
      return $this->cacheNbMembers;
    $membersTable = TableRegistry::getTableLocator()->get('Members');
    $this->cacheNbMembers = $membersTable->find('Members',
    teamId: $this->id,
    memberFilter: 1)->contain('Teams')->count();
    return $this->cacheNbMembers;
  }

  protected function _getCoachs()
  {
    if (!empty($this->cacheCoachs))
      return $this->cacheCoachs;
    $membersTable = TableRegistry::getTableLocator()->get('Members');
    $this->cacheCoachs = $membersTable->find('Members',
    teamId: $this->id,
    memberFilter: 1)->where(['Members.coach =' => 1]);
    return $this->cacheCoachs;
  }

}