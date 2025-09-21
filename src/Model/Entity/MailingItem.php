<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MailingItem Entity
 *
 * @property int $id
 * @property int $mailing_id
 * @property int $member_id
 * @property int $status
 * @property string $tokenhash
 * @property \Cake\I18n\DateTime $confirmation
 *
 * @property \App\Model\Entity\Mailing $mailing
 * @property \App\Model\Entity\Member $member
 */
class MailingItem extends Entity
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
        'mailing_id' => true,
        'member_id' => true,
        'status' => true,
        'tokenhash' => true,
        'confirmation' => true,
        'mailing' => true,
        'member' => true,
    ];
}
