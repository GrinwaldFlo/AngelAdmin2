<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;
use Cake\Collection\Collection;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;

/**
 * Member Entity
 *
 * @property int $id
 * @property bool $checked
 * @property string $first_name
 * @property string $last_name
 * @property \Cake\I18n\Date $date_birth
 * @property int $gender_id
 * @property string $address
 * @property int $postcode
 * @property string $city
 * @property string $phone_mobile
 * @property string $phone_home
 * @property string $email
 * @property bool $email_valid
 * @property string $nationality
 * @property \Cake\I18n\Date $date_arrival
 * @property int $multi_payment
 * @property int $membership_fee_paid
 * @property int $discount
 * @property \Cake\I18n\Date $date_fin
 * @property int $communication_method_id
 * @property bool $active
 * @property int $FegistrationStep
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property int $coach
 * @property bool $registered
 * @property bool $bvr
 * @property string $fullName Get Surname Name
 * @property string $fullNameShort Get Surname Name short
 * @property string $teamString Get string with all owned teams
 * @property string $siteString Get string with all owned teams
 * @property bool $imgExits Does picture member exists
 * @property string $memberPath Get member path
 * @property string $memberUrl Get member url
 * @property string $genderStr Gender as string
 * @property string $communicationMethodStr Way of communication
 * @property string $hash Hash for URL
 * @property string $leaving_comment Get label for select
 *  *
 * @property \App\Model\Entity\Bill[] $bills
 * @property \App\Model\Entity\Presence[] $presences
 * @property \App\Model\Entity\User[] $users
 * @property \App\Model\Entity\Team[] $teams
 * @property \App\Model\Entity\Field[] $fields
 * @property \App\Model\Entity\MemberOrder[] $member_orders
 */
class Member extends Entity
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
    'first_name' => true,
    'last_name' => true,
    'date_birth' => true,
    'gender_id' => true,
    'address' => true,
    'postcode' => true,
    'city' => true,
    'phone_mobile' => true,
    'phone_home' => true,
    'email' => true,
    'email_valid' => true,
    'nationality' => true,
    'date_arrival' => true,
    'multi_payment' => true,
    'membership_fee_paid' => true,
    'discount' => true,
    'date_fin' => true,
    'communication_method_id' => true,
    'active' => true,
    'created' => true,
    'modified' => true,
    'coach' => true,
    'registered' => true,
    'bvr' => true,
    'bills' => true,
    'presences' => true,
    'users' => true,
    'teams' => true,
    'team_string' => true,
    'hash' => true,
    'leaving_comment'=> true,
    'reminder_sent' => true,
    'member_orders' => true,
  ];
  protected function _getFullName()
  {
    return "{$this->first_name} {$this->last_name}";
  }

  protected function _getFullNameShort()
  {
    return $this->first_name . ' ' . mb_substr($this->last_name, 0, 2) . '.';
  }

  protected function _getLabel()
  {
    return $this->_fields['first_name'] . ' ' . $this->_fields['last_name'];
  }

  protected function _getIsAdult()
  {
    return $this->_getAge() >= 18;
  }

  protected function _getAge()
  {
    if (empty($this->date_birth))
      return 0;
    return date_diff($this->date_birth->toDateTimeImmutable(), new \DateTime())->format('%y');
  }

  protected function _getTeamString()
  {
    if (isset($this->_fields['team_string'])) {
      return $this->_fields['team_string'];
    }
    if (empty($this->teams)) {
      return __('No team');
    }
    $teams = new Collection($this->teams);
    $str = $teams->reduce(function ($string, $team) {
      return $string . $team->name . ', ';
    }, '');
    return trim($str, ', ');
  }

  protected function _getSiteString()
  {
    if (isset($this->_fields['site_string'])) {
      return $this->_fields['site_string'];
    }
    if (empty($this->teams)) {
      return __('No site');
    }

    $sites = [];
    foreach ($this->teams as $curTeam) {
      if (!in_array($curTeam->site->city, $sites)) {
        array_push($sites, $curTeam->site->city);
      }
    }

    $teams = new Collection($sites);
    $str = $teams->reduce(function ($string, $team) {
      return $string . $team . ', ';
    }, '');

    return trim($str, ', ');
  }

  protected function _getTeamIds()
  {
    if (empty($this->teams)) {
      return [0];
    }

    $result = [0];

    foreach ($this->teams as $value) {
      array_push($result, $value->id);
    }

    return $result;
  }

  public function CheckFolder()
  {
    $folder = $this->memberPath;

    if (!file_exists($folder))
      mkdir($folder);

    return $folder;
  }

  protected function _getImgExists()
  {
    return file_exists($this->GetImgPath(300));
  }

  /**
   * 
   * @param int $size Size: 100, 200, 300, 1000, 2000
   * @return string
   */
  public function GetImgPath($size = 300)
  {
    return $this->memberPath . $this->id . '_portrait_' . $size . '.jpg';
  }

  /**
   * 
   * @param int $size Size: 100, 200, 300, 1000, 2000
   * @return string
   */
  public function GetImgUrl($size = 300)
  {
    return "{$this->memberUrl}{$this->id}_portrait_{$size}.jpg";
  }

  protected function _getImgIdExists()
  {
    return file_exists($this->GetImgIdPath(300));
  }

  /**
   * 
   * @param int $size Size: 100, 200, 300, 1000, 2000
   * @return string
   */
  public function GetImgIdPath($size = 300)
  {
    return "{$this->memberPath}{$this->id}_id_{$size}.jpg";
  }

  /**
   * 
   * @param int $size Size: 100, 200, 300, 1000, 2000
   * @return string
   */
  public function GetImgIdUrl($size = 300)
  {
    return "{$this->memberUrl}{$this->id}_id_{$size}.jpg";
  }

  public function GetAllMails(): array
  {
    $emails = [$this->email];

    foreach ($this->fields as $field) {
      if ($field->field_type->style == 1 && $field->value != "" && strcasecmp($field->value, $this->email) != 0) {
        array_push($emails, $field->value);
      }
    }
    return $emails;
  }

  protected function _getMemberPath()
  {
    return WWW_ROOT . 'img/members/' . $this->hash . '/';
  }

  protected function _getMemberUrl()
  {
    return "/img/members/{$this->hash}/";
  }

  public function GetDocPath($type)
  {
    return "{$this->memberPath}{$this->id}_{$type}.pdf";
  }

  public function GetDocUrl($type)
  {
    return "{$this->memberUrl}{$this->id}_{$type}.pdf";
  }

  public function DocExists($type)
  {
    $file = $this->GetDocPath($type);
    return file_exists($file);
  }

  public function GetRegPath($year)
  {
    return "{$this->memberPath}{$this->id}_Registration_{$year}.pdf";
  }

  public function GetRegUrl($year)
  {
    return "{$this->memberUrl}{$this->id}_Registration_{$year}.pdf";
  }

  public function RegExists($year)
  {
    $file = $this->GetRegPath($year);
    return file_exists($file);
  }

  protected function _getInvoicesOpen()
  {
    $bills = TableRegistry::getTableLocator()->get('Bills');
    return $bills->find('all')->where(['member_id =' => $this->id, 'canceled =' => 0, 'paid =' => 0])->count();
  }

  protected function _getInvoicesTotal()
  {
    $bills = TableRegistry::getTableLocator()->get('Bills');
    return $bills->find('all')->where(['member_id =' => $this->id, 'canceled =' => 0])->count();
  }

  public function SignStatus($year)
  {
    $registrations = TableRegistry::getTableLocator()->get('Registrations');

    $registration = $registrations->find('all')
      ->where(['year =' => $year, 'member_id =' => $this->id])
      ->select(['id', 'member_id', 'validation_id'])->first();

    if (empty($registration))
      return 0;

    if ($registration->validation_id == 0)
      return 1;

    return 2;
  }

  public function RegistrationStep($year)
  {
    if (!$this->active)
      return 2;

    if (!$this->checked)
      return 1;

    if (!$this->registered) {
      switch ($this->SignStatus($year)) {
        case 0: // Not signed
          return 3;
        case 1: // Not approved
          return 4;
        case 2: // Approved (should not append)
          return 5;
      }
    }
    if (empty($this->teams))
      return 6;

    return 0;
  }

  protected function _getGenderList()
  {
    return [0 => __('Unknown'), 1 => __('Female'), 2 => __('Male')];
  }

  protected function _getGenderStr()
  {
    return $this->_getGenderList()[$this->gender_id];
  }

  protected function _getCommunicationMethodList()
  {
    return [0 => __('-'), 1 => __('Email'), 2 => __('Letter')];
  }

  protected function _getCommunicationMethodStr()
  {
    return $this->_getCommunicationMethodList()[$this->communication_method_id];
  }

  public function MembershipFee($max)
  {
    $amount = 0;
    if (empty($this->teams)) {
      return -1;
    }

    foreach ($this->teams as $value) {
      $amount += $value->membership_fee;
    }

    $amount -= $this->discount;

    if ($amount > $max)
      return $max;
    return $amount;
  }

  public function HasMembershipFee($config)
  {
    $bills = TableRegistry::getTableLocator()->get('Bills');
    $nbMFee = $bills->find('all')->where(['member_id =' => $this->id, 'canceled =' => 0, 'label LIKE' => $bills->GetFeeLabel($config) . "%"])->count();
    return $nbMFee > 0;
  }

  public function MembershipFeePaid($config)
  {
    $bills = TableRegistry::getTableLocator()->get('Bills');
    $query = $bills->find('all')->where(['member_id =' => $this->id, 'canceled =' => 0, 'paid =' => 1, 'label LIKE' => $bills->GetFeeLabel($config) . "%"]);

    $result = $query->select(['sum' => $query->func()->sum('Bills.amount')])->first();

    return $result->sum;
  }

}
