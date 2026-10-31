<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MemberOrdersTable;
use App\Model\Table\ShopItemsTable;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\MemberOrdersTable
 */
class MemberOrdersTableTest extends TestCase
{
    protected array $fixtures = ['app.ShopItems', 'app.MemberOrders', 'app.Members', 'app.Bills', 'app.Sites'];

    private MemberOrdersTable $MemberOrders;

    private int $itemId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->MemberOrders = $this->getTableLocator()->get('MemberOrders');
        $items = $this->getTableLocator()->get('ShopItems');
        $this->itemId = $items->saveOrFail($items->newEntity(['label' => 'Shirt', 'price' => 10, 'category' => ShopItemsTable::CATEGORY_OTHER]))->id;
    }

    public function testValidationDefault(): void
    {
        $order = $this->MemberOrders->newEntity(['shop_item_id' => $this->itemId, 'member_id' => 1, 'quantity' => 2]);
        $this->assertSame([], $order->getErrors());

        $this->assertArrayHasKey('quantity', $this->MemberOrders->newEntity(['shop_item_id' => $this->itemId, 'member_id' => 1])->getErrors());
        $this->assertArrayHasKey('greaterThan', $this->MemberOrders->newEntity(['quantity' => 0])->getError('quantity'));
        $this->assertArrayHasKey('integer', $this->MemberOrders->newEntity(['quantity' => 'a'])->getError('quantity'));
    }

    public function testSaveWithAndWithoutBill(): void
    {
        $order = $this->MemberOrders->newEntity(['shop_item_id' => $this->itemId, 'member_id' => 1, 'quantity' => 1]);
        $this->assertNotFalse($this->MemberOrders->save($order));

        $saved = $this->MemberOrders->get($order->id, contain: ['ShopItems', 'Members', 'Bills']);
        $this->assertFalse($saved->delivered);
        $this->assertSame('Shirt', $saved->shop_item->label);
        $this->assertNull($saved->bill);

        $order = $this->MemberOrders->newEntity(['shop_item_id' => $this->itemId, 'member_id' => 1, 'quantity' => 1, 'bill_id' => 1]);
        $this->assertNotFalse($this->MemberOrders->save($order));
    }

    public function testBuildRules(): void
    {
        $order = $this->MemberOrders->newEntity(['shop_item_id' => 999, 'member_id' => 999, 'bill_id' => 999, 'quantity' => 1]);

        $this->assertFalse($this->MemberOrders->save($order));
        $this->assertSame(['bill_id', 'member_id', 'shop_item_id'], $this->sortedKeys($order->getErrors()));
    }

    private function sortedKeys(array $errors): array
    {
        $keys = array_keys($errors);
        sort($keys);

        return $keys;
    }
}
