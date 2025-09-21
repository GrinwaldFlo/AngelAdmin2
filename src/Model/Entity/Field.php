<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Field Entity
 *
 * @property int $member_id
 * @property int $field_type_id
 * @property string|null $value
 *
 * @property \App\Model\Entity\Member $member
 * @property \App\Model\Entity\FieldType $field_type
 */
class Field extends Entity
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
        'value' => true,
        'member' => true,
        'field_type' => true,
    ];
}
