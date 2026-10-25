<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validation;

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

  /**
   * True when the cached counters are known (they are -1 until computed)
   */
  public function hasCounts(): bool
  {
    return $this->present != -1 && $this->absent != -1 && $this->excused != -1 && $this->late != -1;
  }

  /**
   * Set the cached counters from the number of presences by state
   * @param array $nbByState [state => count], missing states count 0
   */
  public function setCounts(array $nbByState): void
  {
    $this->present = (int)($nbByState[Presence::PRESENT] ?? 0);
    $this->absent = (int)($nbByState[Presence::ABSENT] ?? 0);
    $this->excused = (int)($nbByState[Presence::EXCUSED] ?? 0);
    $this->late = (int)($nbByState[Presence::LATE] ?? 0);
  }

  public function CheckPresences($force)
  {
    if (!$force && $this->hasCounts())
    {
      return;
    }
    $meetings = TableRegistry::getTableLocator()->get('Meetings');
    $this->setCounts($meetings->countPresences([$this->id])[$this->id] ?? []);
    $meetings->save($this);
  }

  /**
   * Only http(s) links are allowed, other schemes (javascript:, data:, ...) could run code when clicked
   */
  public static function isWebUrl(?string $url): bool
  {
    return $url !== null && preg_match('#^https?://#i', $url) === 1 && Validation::url($url, true);
  }

  /**
   * Link of the meeting, null if empty or not a web link
   */
  public function webUrl(): ?string
  {
    return self::isWebUrl($this->url) ? $this->url : null;
  }

}
