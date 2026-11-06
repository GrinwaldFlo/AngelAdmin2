<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ShopItem;
use App\Model\Entity\ShopStock;
use Cake\Database\Exception\QueryException;
use Cake\I18n\DateTime;
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
     * @param array<int, array<string, \App\Model\Entity\ShopStock>>|null $stocks stocks already loaded with stocksOf(), null = read it
     */
    public function orderable(ShopItem $item, ?string $attribute, int $max, ?array $stocks = null): int
    {
        if ($item->allow_out_of_stock) {
            return $max;
        }
        $quantity = $stocks === null
            ? $this->getFor($item->id, $attribute)->quantity
            : ($stocks[$item->id][(string)$attribute]->quantity ?? 0);

        return min($max, max(0, (int)$quantity));
    }

    /**
     * Items ordered by a member: removed from the stock
     * Unless the item allows out of stock orders, the stock is checked in the same query: two orders at the same time can't take the same items
     *
     * @return bool false if there is not enough stock (nothing changed)
     */
    public function reserve(ShopItem $item, ?string $attribute, int $quantity): bool
    {
        $conditions = $item->allow_out_of_stock ? [] : ['quantity >=' => $quantity];

        return $this->update($item->id, $attribute, ['quantity' => 'quantity - ' . $quantity], $conditions);
    }

    /**
     * Order deleted before it was given: back to the stock
     */
    public function release(int $itemId, ?string $attribute, int $quantity): void
    {
        $this->update($itemId, $attribute, ['quantity' => 'quantity + ' . $quantity]);
    }

    /**
     * Order changed to another attribute
     *
     * @return bool false if there is not enough stock of the new attribute (nothing changed)
     */
    public function move(ShopItem $item, ?string $from, ?string $to, int $quantity): bool
    {
        if (!$this->reserve($item, $to, $quantity)) {
            return false;
        }
        $this->release($item->id, $from, $quantity);

        return true;
    }

    /**
     * Items ordered from the supplier
     */
    public function markOrdered(int $itemId, ?string $attribute, int $quantity): void
    {
        $this->update($itemId, $attribute, ['on_order' => 'on_order + ' . $quantity]);
    }

    /**
     * Items received from the supplier: no longer on order, added to the stock
     */
    public function receive(int $itemId, ?string $attribute, int $quantity): void
    {
        $this->update($itemId, $attribute, [
            'quantity' => 'quantity + ' . $quantity,
            'on_order' => sprintf('CASE WHEN on_order > %1$d THEN on_order - %1$d ELSE 0 END', $quantity),
        ]);
    }

    /**
     * Stock counted by hand
     */
    public function setQuantity(int $itemId, ?string $attribute, int $quantity): void
    {
        $this->update($itemId, $attribute, ['quantity' => (string)$quantity]);
    }

    /**
     * Change the counters in one query: a change made at the same time by another request is not lost
     * The stock is created when the item and attribute have none yet
     *
     * @param array<string, string> $set column => SQL expression on the integer columns (built from integers only)
     * @param array $conditions extra conditions, the stock is not changed if they are not met
     * @return bool false if the conditions are not met
     */
    private function update(int $itemId, ?string $attribute, array $set, array $conditions = []): bool
    {
        $where = ['shop_item_id' => $itemId, 'attribute' => (string)$attribute] + $conditions;
        if ($this->runUpdate($set, $where)) {
            return true;
        }
        if ($conditions !== []) {
            // A missing stock is 0: it can't meet a minimum quantity
            return false;
        }
        try {
            $this->insertQuery()
                ->insert(['shop_item_id', 'attribute', 'quantity', 'on_order', 'modified'])
                ->values(['shop_item_id' => $itemId, 'attribute' => (string)$attribute, 'quantity' => 0, 'on_order' => 0, 'modified' => DateTime::now()])
                ->execute();
        } catch (QueryException) {
            // Created at the same time by another request (unique item and attribute): it is updated below
        }

        return $this->runUpdate($set, $where);
    }

    /**
     * @param array<string, string> $set
     * @return bool true if a stock was changed
     */
    private function runUpdate(array $set, array $where): bool
    {
        $query = $this->updateQuery();
        foreach ($set as $column => $expression) {
            $query->set($column, $query->expr($expression));
        }

        return $query->set('modified', DateTime::now())->where($where)->execute()->rowCount() > 0;
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
            $offered = $item->stockKeys();
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
