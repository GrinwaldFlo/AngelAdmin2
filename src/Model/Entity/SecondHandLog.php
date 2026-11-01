<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SecondHandLog Entity: copy of the text of a second-hand item, kept when the item is deleted
 *
 * @property int $id
 * @property int $item_id
 * @property int|null $member_id
 * @property string $seller_name
 * @property string $category
 * @property string $description
 * @property string $size
 * @property string $price
 * @property int $status
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $closed date of the sale or of the removal
 */
class SecondHandLog extends Entity
{
    public const FOR_SALE = 0;
    public const SOLD = 1;
    // Removed by the seller
    public const REMOVED = 2;
    // Deleted by the admin
    public const DELETED = 3;

    protected array $_accessible = [];

    /**
     * Final status, an item still for sale may be expired
     * @param int $months duration set by the admin
     */
    public function statusLabel(int $months): string
    {
        return match ($this->status) {
            self::SOLD => __('Sold'),
            self::REMOVED => __('Removed by the seller'),
            self::DELETED => __('Deleted by the admin'),
            default => $this->created !== null && $this->created < SecondHandItem::expiryLimit($months) ? __('Expired') : __('For sale'),
        };
    }
}
