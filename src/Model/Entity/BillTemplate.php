<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * BillTemplate Entity
 *
 * @property int $id
 * @property string|null $label
 * @property int $amount
 * @property bool $membership_fee
 * @property int $site_id
 *
 * @property \App\Model\Entity\Site $site
 */
class BillTemplate extends Entity
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
        'label' => true,
        'amount' => true,
        'membership_fee' => true,
        'site_id' => true,
        'site' => true,
    ];
}
