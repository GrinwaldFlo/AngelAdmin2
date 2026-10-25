<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * Meeting Entity
 *
 * @property int $id
 * @property \Cake\I18n\DateTime $meeting_date
 * @property int $team_id
 * @property string $name
 * @property string $url
 * @property bool $big_event
 * @property bool $doodle
 * @property string $PresencesStr
 * @property int $present
 * @property int $absent
 * @property int $excused
 * @property int $late
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Team $team
 * @property \App\Model\Entity\Presence[] $presences
 */
class Meeting extends Entity
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
      'meeting_date' => true,
      'team_id' => true,
      'name' => true,
      'created' => true,
      'modified' => true,
      'team' => true,
      'presences' => true,
      'big_event' => true,
      'url' => true,
      'doodle' => true,
  ];
  protected function _getPresencesStr()
  {
    $this->CheckPresences(false);
    return __('{0}/{1}/{2}/{3}', $this->present, $this->absent, $this->excused, $this->late); // $this->_fields['first_name'] . ' ' . $this->_fields['last_name'];
  }

  /**
   * Number of members by state, recomputed only when the cached counters are unknown
   * @return array [Presence::PRESENT => n, Presence::ABSENT => n, Presence::EXCUSED => n, Presence::LATE => n]
   */
  public function counts(): array
  {
    $this->CheckPresences(false);
    return [
      Presence::PRESENT => (int)$this->present,
      Presence::ABSENT => (int)$this->absent,
      Presence::EXCUSED => (int)$this->excused,
      Presence::LATE => (int)$this->late,
    ];
  }

  public function CheckPresences($force)
  {
    if (!$force && $this->present != -1 && $this->absent != -1 && $this->excused != -1 && $this->late != -1)
    {
      return;
    }
    $presences = TableRegistry::getTableLocator()->get('Presences');
    $query = $presences->find();
    $rows = $query->select(['state', 'nb' => $query->func()->count('*')])
      ->where(['meeting_id' => $this->id])
      ->groupBy(['state'])
      ->disableHydration()
      ->all()
      ->combine('state', 'nb')
      ->toArray();

    $this->present = (int)($rows[Presence::PRESENT] ?? 0);
    $this->absent = (int)($rows[Presence::ABSENT] ?? 0);
    $this->excused = (int)($rows[Presence::EXCUSED] ?? 0);
    $this->late = (int)($rows[Presence::LATE] ?? 0);

    TableRegistry::getTableLocator()->get('Meetings')->save($this);
  }

}
