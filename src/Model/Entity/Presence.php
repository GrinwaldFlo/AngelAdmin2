<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;

/**
 * Presence Entity
 *
 * @property int $id
 * @property int $meeting_id
 * @property int $member_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property int $state
 *
 * @property \App\Model\Entity\Meeting $meeting
 * @property \App\Model\Entity\Member $member
 */
class Presence extends Entity
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
      'meeting_id' => true,
      'member_id' => true,
      'created' => true,
      'modified' => true,
      'state' => true,
      'meeting' => true,
      'member' => true,
  ];
  protected function _getStatusHtml()
  {
    switch ($this->state)
    {
      case 0: // Absent
        return 'danger';
      case 1: // Present
        return 'success';
      case 2: // Excused
        return 'primary';
      case 3: // Late
        return 'warning';
      case -1: // No
        return 'black';
      default:
        return "XXX";
    }
  }

  public function Season($firstDayOfYear): string
  {
    $curYear = $this->meeting->meeting_date->i18nFormat('YYYY');
    
    $firstDay = new \DateTime($firstDayOfYear . '.' . $curYear);
    
    if($this->meeting->meeting_date < $firstDay)
      return ($curYear-1).'-'.$curYear;
    else
      return $curYear.'-'.($curYear + 1);
  }

}