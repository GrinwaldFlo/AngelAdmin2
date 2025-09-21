<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MemberOrder Entity
 *
 * @property int $id
 * @property int $shop_item_id
 * @property int $member_id
 * @property int|null $bill_id
 * @property int $quantity
 * @property bool $delivered
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\ShopItem $shop_item
 * @property \App\Model\Entity\Member $member
 * @property \App\Model\Entity\Bill|null $bill
 */
class MemberOrder extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'shop_item_id' => true,
        'member_id' => true,
        'bill_id' => true,
        'quantity' => true,
        'delivered' => true,
        'created' => true,
        'modified' => true,
        'shop_item' => true,
        'member' => true,
        'bill' => true,
    ];
}
