<?php
declare(strict_types=1);
namespace App\Model\Entity;
use Cake\ORM\Entity;

/**
 * Bill Entity
 *
 * @property int $id
 * @property int $member_id
 * @property string $label
 * @property int $amount
 * @property bool $printed
 * @property bool $paid
 * @property int $reminder
 * @property \Cake\I18n\Date $due_date
 * @property \Cake\I18n\Date $due_date_ori
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property bool $link_membership_fee
 * @property bool $canceled
 * @property int $state_id
 * @property string $tokenhash
 * @property \Cake\I18n\DateTime|null $confirmation
 * @property string $BillPath PDF Full path
 * @property string $QRPath QR Code Bill Full path
 * @property bool $BillExists Check PDF file exists
 * @property int $site_id
 * @property string $BillUrl Get PDF url
 * @property string $StatusHtml Get label with color for html status
 * @property string $StatusString Get string status
 * @property int $Reference
 *
 * @property \App\Model\Entity\Member $member
 * @property \App\Model\Entity\Site $site
 */
class Bill extends Entity
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
      'member_id' => true,
      'label' => true,
      'amount' => true,
      'printed' => true,
      'paid' => true,
      'reminder' => true,
      'due_date' => true,
      'due_date_ori' => true,
      'created' => true,
      'modified' => true,
      'link_membership_fee' => true,
      'canceled' => true,
      'state_id' => true,
      'tokenhash' => true,
      'confirmation' => true,
      'site_id' => true,
      'member' => true,
      'site' => true,
  ];
  protected function _getStatusString()
  {
    if ($this->canceled)
    {
      return __('Canceled');
    }
    if ($this->paid)
    {
      return __('Paid');
    }
    if ($this->printed)
    {
      if ($this->due_date < \Cake\I18n\FrozenDate::today())
        return __('Late');
      else
        return __('Sent');
    }
    return __('Draft'); // $bills->status
  }

  protected function _getStatusHtml()
  {
    if ($this->canceled)
    {
      return 'dark';
    }
    if ($this->paid)
    {
      return 'success';
    }
    if ($this->printed)
    {
      if ($this->due_date < \Cake\I18n\FrozenDate::today())
        return 'danger';
      else
        return 'info';
    }
    return 'warning'; // Draft
  }

  protected function _getBillPath()
  {
    return $this->member->MemberPath . 'Invoice_' . $this->Reference . '.pdf';
  }

  protected function _getBillUrl()
  {
    return $this->member->MemberUrl . 'Invoice_' . $this->Reference . '.pdf';
  }

  protected function _getQRPath()
  {
    return $this->member->MemberPath . 'QR_' . $this->Reference . '.svg';
  }

  protected function _getBillExists()
  {
    return file_exists($this->BillPath);
  }

  public function DeleteBillPdf()
  {
    unlink($this->BillPath);
  }

  protected function _getStateList()
  {
    return [0 => __('Draft'), 1 => __('Sent'), 2 => __x('In the way, it has been read', 'Read')];
  }

  protected function _getStateStr()
  {
    return $this->_getStateList()[$this->state_id];
  }

  protected function _getReference()
  {
    return $this->id + $this->site->add_invoice_num;
  }

}