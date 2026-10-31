<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ShopItemsTable;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\ShopItemsTable
 */
class ShopItemsTableTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.ShopItems', 'app.MemberOrders', 'app.Members', 'app.Bills', 'app.Sites'];

    private ShopItemsTable $ShopItems;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ShopItems = $this->getTableLocator()->get('ShopItems');
    }

    private function addItem(string $label, bool $active = true): int
    {
        return $this->ShopItems->saveOrFail($this->ShopItems->newEntity(
            ['label' => $label, 'price' => 10, 'category' => ShopItemsTable::CATEGORY_EQUIPMENT, 'active' => $active]
        ))->id;
    }

    private function addOrder(int $itemId, ?int $billId): void
    {
        $orders = $this->getTableLocator()->get('MemberOrders');
        $orders->saveOrFail($orders->newEntity(['shop_item_id' => $itemId, 'member_id' => 1, 'bill_id' => $billId, 'quantity' => 1, 'delivered' => false]));
    }

    public function testValidationDefault(): void
    {
        $item = $this->ShopItems->newEntity(['label' => 'Shirt', 'price' => '25.50', 'category' => ShopItemsTable::CATEGORY_TRAVEL]);
        $this->assertSame([], $item->getErrors());

        $item = $this->ShopItems->newEntity([]);
        $this->assertSame(['label', 'price', 'category'], array_keys($item->getErrors()));

        $item = $this->ShopItems->newEntity(['label' => '', 'price' => 0, 'category' => 99]);
        $this->assertArrayHasKey('label', $item->getErrors());
        $this->assertArrayHasKey('greaterThan', $item->getError('price'));
        $this->assertArrayHasKey('inList', $item->getError('category'));

        $item = $this->ShopItems->newEntity(['label' => str_repeat('a', 256), 'price' => -1, 'category' => 1]);
        $this->assertArrayHasKey('maxLength', $item->getError('label'));
        $this->assertArrayHasKey('greaterThan', $item->getError('price'));
    }

    public function testCategoryOptionsAndLabel(): void
    {
        $options = ShopItemsTable::getCategoryOptions();

        $this->assertSame([ShopItemsTable::CATEGORY_OTHER, ShopItemsTable::CATEGORY_TRAVEL, ShopItemsTable::CATEGORY_EQUIPMENT], array_keys($options));
        $this->assertSame($options[ShopItemsTable::CATEGORY_TRAVEL], ShopItemsTable::getCategoryLabel(ShopItemsTable::CATEGORY_TRAVEL));
        $this->assertSame(__('Unknown'), ShopItemsTable::getCategoryLabel(99));
    }

    public function testFindActive(): void
    {
        $this->addItem('Shirt');
        $this->addItem('Old shirt', false);

        $this->assertSame(['Shirt'], $this->ShopItems->find('active')->all()->extract('label')->toList());
    }

    public function testFindOrdersByPaymentStatus(): void
    {
        $this->addBill(10, 1, ['paid' => true]);
        $this->addBill(11, 1);
        $paidItem = $this->addItem('Paid');
        $unpaidItem = $this->addItem('Unpaid');
        $noBillItem = $this->addItem('No bill');
        $this->addOrder($paidItem, 10);
        $this->addOrder($unpaidItem, 11);
        $this->addOrder($noBillItem, null);

        $labels = fn ($query) => $query->all()->extract('label')->toList();

        $this->assertSame(['Paid'], $labels($this->ShopItems->find('ordersByPaymentStatus', paid: true)));
        $unpaid = $labels($this->ShopItems->find('ordersByPaymentStatus', paid: false));
        sort($unpaid);
        $this->assertSame(['No bill', 'Unpaid'], $unpaid);
        $this->assertCount(3, $labels($this->ShopItems->find('ordersByPaymentStatus')));
    }
}
