<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use App\Model\Table\ShopItemsTable;

/**
 * ShopItem Entity
 *
 * @property int $id
 * @property string $label
 * @property float $price
 * @property int $category
 * @property bool $active
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\MemberOrder[] $member_orders
 */
class ShopItem extends Entity
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
        'label' => true,
        'price' => true,
        'category' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'member_orders' => true,
    ];

    /**
     * Get category label
     *
     * @return string
     */
    protected function _getCategoryLabel(): string
    {
        return ShopItemsTable::getCategoryLabel($this->category);
    }
}
