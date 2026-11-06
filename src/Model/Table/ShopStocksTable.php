<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ShopItem;
use App\Model\Entity\ShopStock;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Stock of the shop items by attribute ('' = item without attribute)
 *
 * The member orders are deducted when they are placed: a negative quantity is the number of items to order.
 * The attribute is a copy of the text, a removed attribute keeps its stock.
 *
 * @property \App\Model\Table\ShopItemsTable&\Cake\ORM\Association\BelongsTo $ShopItems
 *
 * @method \App\Model\Entity\ShopStock newEmptyEntity()
 * @method \App\Model\Entity\ShopStock newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ShopStock|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ShopStock saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ShopStocksTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('shop_stocks');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('ShopItems', [
            'foreignKey' => 'shop_item_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('quantity')
            ->integer('on_order')
            ->scalar('attribute')
            ->maxLength('attribute', ShopItemsTable::ATTRIBUTE_MAX_LENGTH);

        return $validator;
    }

    /**
     * Stock of an item and attribute, a new (unsaved) empty stock if there is none yet
     */
    public function getFor(int $itemId, ?string $attribute): ShopStock
    {
        return $this->find()->where(['shop_item_id' => $itemId, 'attribute' => (string)$attribute])->first()
            ?? $this->newEntity(['shop_item_id' => $itemId, 'attribute' => (string)$attribute, 'quantity' => 0, 'on_order' => 0]);
    }

    /**
     * Quantity a member can order: limited by the stock unless the item allows out of stock orders
     *
     * @param int $max maximum quantity of an order line
     */
    public function orderable(ShopItem $item, ?string $attribute, int $max): int
    {
        if ($item->allow_out_of_stock) {
            return $max;
        }

        return min($max, max(0, $this->getFor($item->id, $attribute)->quantity));
    }

    /**
     * Items ordered by a member: removed from the stock
     */
    public function reserve(int $itemId, ?string $attribute, int $quantity): void
    {
        $this->addTo($itemId, $attribute, -$quantity);
    }

    /**
     * Order deleted before it was given: back to the stock
     */
    public function release(int $itemId, ?string $attribute, int $quantity): void
    {
        $this->addTo($itemId, $attribute, $quantity);
    }

    /**
     * Order changed to another attribute
     */
    public function move(int $itemId, ?string $from, ?string $to, int $quantity): void
    {
        $this->release($itemId, $from, $quantity);
        $this->reserve($itemId, $to, $quantity);
    }

    /**
     * Items ordered from the supplier
     */
    public function markOrdered(int $itemId, ?string $attribute, int $quantity): void
    {
        $this->addTo($itemId, $attribute, 0, $quantity);
    }

    /**
     * Items received from the supplier: no longer on order, added to the stock
     */
    public function receive(int $itemId, ?string $attribute, int $quantity): void
    {
        $stock = $this->getFor($itemId, $attribute);
        $stock->quantity += $quantity;
        $stock->on_order = max(0, $stock->on_order - $quantity);
        $this->saveOrFail($stock);
    }

    /**
     * Stock counted by hand
     */
    public function setQuantity(int $itemId, ?string $attribute, int $quantity): void
    {
        $stock = $this->getFor($itemId, $attribute);
        $stock->quantity = $quantity;
        $this->saveOrFail($stock);
    }

    /**
     * Add to the counters in one query, two orders at the same time don't lose a change
     */
    private function addTo(int $itemId, ?string $attribute, int $quantity, int $onOrder = 0): void
    {
        $stock = $this->getFor($itemId, $attribute);
        if ($stock->isNew()) {
            $this->saveOrFail($stock);
        }
        $query = $this->updateQuery();
        $query
            ->set([
                'quantity' => $query->newExpr('quantity + ' . $quantity),
                'on_order' => $query->newExpr('on_order + ' . $onOrder),
            ])
            ->where(['id' => $stock->id])
            ->execute();
    }

    /**
     * Stocks of the items
     *
     * @param int[] $itemIds
     * @return array<int, array<string, \App\Model\Entity\ShopStock>> [item id][attribute]
     */
    public function stocksOf(array $itemIds): array
    {
        $stocks = [];
        if ($itemIds === []) {
            return $stocks;
        }
        foreach ($this->find()->where(['shop_item_id IN' => $itemIds]) as $stock) {
            $stocks[$stock->shop_item_id][$stock->attribute] = $stock;
        }

        return $stocks;
    }

    /**
     * Ordered items of a city, aggregated by item and attribute
     * Rows of the offered attributes of the active items, and of any attribute still having orders to give or stock
     *
     * @return array<int, array{item: \App\Model\Entity\ShopItem, attribute: string, offered: bool, pending: int, stock: \App\Model\Entity\ShopStock}>
     */
    public function summary(int $siteId): array
    {
        $items = $this->ShopItems->find('inSite', siteId: $siteId)->orderBy(['ShopItems.label' => 'ASC'])->all()->toList();
        $itemIds = array_map(fn($item) => $item->id, $items);
        $stocks = $this->stocksOf($itemIds);
        $pending = $this->pendingOf($itemIds);

        $rows = [];
        foreach ($items as $item) {
            $offered = $item->hasAttributes() ? $item->getAttributeList() : [''];
            $keys = $item->active ? $offered : [];
            foreach ($stocks[$item->id] ?? [] as $attribute => $stock) {
                if ($stock->quantity != 0 || $stock->on_order != 0) {
                    $keys[] = (string)$attribute;
                }
            }
            $keys = array_merge($keys, array_map('strval', array_keys($pending[$item->id] ?? [])));

            foreach (array_unique($keys) as $attribute) {
                $rows[] = [
                    'item' => $item,
                    'attribute' => $attribute,
                    'offered' => $item->active && in_array($attribute, $offered, true),
                    'pending' => $pending[$item->id][$attribute] ?? 0,
                    'stock' => $stocks[$item->id][$attribute]
                        ?? $this->newEntity(['shop_item_id' => $item->id, 'attribute' => $attribute, 'quantity' => 0, 'on_order' => 0]),
                ];
            }
        }

        return $rows;
    }

    /**
     * Quantities ordered by the members and not given yet
     *
     * @param int[] $itemIds
     * @return array<int, array<string, int>> [item id][attribute]
     */
    private function pendingOf(array $itemIds): array
    {
        $pending = [];
        if ($itemIds === []) {
            return $pending;
        }
        $orders = $this->ShopItems->MemberOrders;
        $query = $orders->find();
        $query
            ->select(['shop_item_id', 'attribute', 'total' => $query->func()->sum('quantity')])
            ->where(['shop_item_id IN' => $itemIds, 'delivered' => false])
            ->groupBy(['shop_item_id', 'attribute']);
        foreach ($query as $row) {
            $attribute = (string)$row->attribute;
            $pending[$row->shop_item_id][$attribute] = ($pending[$row->shop_item_id][$attribute] ?? 0) + (int)$row->total;
        }

        return $pending;
    }
}
