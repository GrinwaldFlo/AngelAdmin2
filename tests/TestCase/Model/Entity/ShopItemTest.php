<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\ShopItem;
use App\Model\Table\ShopItemsTable;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\ShopItem
 */
class ShopItemTest extends TestCase
{
    public function testCategoryLabel(): void
    {
        $this->assertSame(ShopItemsTable::getCategoryLabel(ShopItemsTable::CATEGORY_TRAVEL), (new ShopItem(['category' => ShopItemsTable::CATEGORY_TRAVEL]))->category_label);
        $this->assertSame(__('Unknown'), (new ShopItem(['category' => 99]))->category_label);
    }
}
