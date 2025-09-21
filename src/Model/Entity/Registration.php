<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Registration Entity
 *
 * @property int $id
 * @property string|resource|null $signature_member
 * @property string|resource|null $signature_parent
 * @property int $member_id
 * @property int $validation_id
 * @property int $year
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Member $member
 * @property \App\Model\Entity\Validation $validation
 */
class Registration extends Entity
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
        'signature_member' => true,
        'signature_parent' => true,
        'member_id' => true,
        'validation_id' => true,
        'year' => true,
        'created' => true,
        'modified' => true,
        'member' => true,
        'validation' => true,
    ];
}
