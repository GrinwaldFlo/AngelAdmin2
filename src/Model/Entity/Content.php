<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;

/**
 * Content Entity
 *
 * @property int $id
 * @property string|null $text
 * @property int $location
 * @property string $url
 * @property int $sort
 * @property int $team_id
 *
 * @property \App\Model\Entity\Team $team
 */
class Content extends Entity
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
      'text' => true,
      'location' => true,
      'url' => true,
      'team_id' => true,
      'team' => true,
      'sort' => true,
  ];
  public function getLocationList()
  {
    return [
        0 => 'No location', 
        1 => 'Top menu', 
        2 => 'Home message', 
        3 => 'Info page', 
        4 => 'Home page',
        5 => 'Title event',
        6 => 'Title big event',
        7 => 'Title doodle',
        8 => 'Comment event',
        9 => 'Comment big event',
        10 => 'Comment doodle',
        11 => 'Subventions',
        
        ];
  }

  protected function _getLocationStr()
  {
    return $this->getLocationList()[$this->location];
  }

}