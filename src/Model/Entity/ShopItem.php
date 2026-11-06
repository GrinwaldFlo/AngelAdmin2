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
 * @property int $site_id City of the item
 * @property string|null $attributes Possible attributes (e.g. sizes), one per line, empty = nothing to choose
 * @property bool $allow_out_of_stock The item can be ordered without stock
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\MemberOrder[] $member_orders
 * @property \App\Model\Entity\ShopStock[] $shop_stocks
 * @property \App\Model\Entity\Site $site
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
        'attributes' => true,
        'allow_out_of_stock' => true,
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

    /**
     * Clean list of attributes typed one per line: trimmed, without empty lines nor duplicates
     *
     * @param string|null $text
     * @return string[]
     */
    public static function parseAttributes(?string $text): array
    {
        $list = [];
        foreach (preg_split('/\R/u', (string)$text) as $line) {
            $line = trim($line);
            if ($line !== '' && !in_array($line, $list, true)) {
                $list[] = $line;
            }
        }

        return $list;
    }

    /**
     * Attributes the member can choose today
     *
     * @return string[]
     */
    public function getAttributeList(): array
    {
        return self::parseAttributes($this->attributes);
    }

    public function hasAttributes(): bool
    {
        return $this->getAttributeList() !== [];
    }

    /**
     * Key of an attribute in the order form: the text may contain characters not allowed in a field name
     */
    public static function attributeKey(string $attribute): string
    {
        return substr(md5($attribute), 0, 12);
    }

    /**
     * Attribute of the current list matching a key of the order form
     *
     * @return string|null null if the attribute is not offered anymore
     */
    public function attributeFromKey(string $key): ?string
    {
        foreach ($this->getAttributeList() as $attribute) {
            if (self::attributeKey($attribute) === $key) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Label with the chosen attribute, e.g. "T-shirt - M"
     */
    public function labelWith(?string $attribute): string
    {
        return $attribute === null || $attribute === '' ? (string)$this->label : $this->label . ' - ' . $attribute;
    }
}
