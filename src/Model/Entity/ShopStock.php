<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Stock of a shop item for one attribute
 *
 * @property int $id
 * @property int $shop_item_id
 * @property string $attribute '' = item without attribute
 * @property int $quantity Items available, the member orders are already deducted: negative = to order
 * @property int $on_order Items ordered from the supplier, not received yet
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\ShopItem $shop_item
 */
class ShopStock extends Entity
{
    protected array $_accessible = [
        'shop_item_id' => true,
        'attribute' => true,
        'quantity' => true,
        'on_order' => true,
    ];

    /**
     * Items still to order from the supplier
     */
    public function toOrder(): int
    {
        return max(0, -((int)$this->quantity + (int)$this->on_order));
    }
}
