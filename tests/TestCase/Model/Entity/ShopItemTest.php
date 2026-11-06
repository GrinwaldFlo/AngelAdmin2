<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\ShopItem;
use App\Model\Entity\ShopStock;
use App\Model\Table\ShopItemsTable;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\ShopItem
 * @uses \App\Model\Entity\ShopStock
 */
class ShopItemTest extends TestCase
{
    public function testCategoryLabel(): void
    {
        $this->assertSame(ShopItemsTable::getCategoryLabel(ShopItemsTable::CATEGORY_TRAVEL), (new ShopItem(['category' => ShopItemsTable::CATEGORY_TRAVEL]))->category_label);
        $this->assertSame(__('Unknown'), (new ShopItem(['category' => 99]))->category_label);
    }

    public function testParseAttributes(): void
    {
        $this->assertSame([], ShopItem::parseAttributes(null));
        $this->assertSame([], ShopItem::parseAttributes(" \n\r\n "));
        $this->assertSame(['S', 'M', 'XL', 'Taille unique'], ShopItem::parseAttributes(" S\r\nM\n\nM\rXL \n Taille unique "));
        // Case matters: two different attributes
        $this->assertSame(['m', 'M'], ShopItem::parseAttributes("m\nM"));
    }

    public function testAttributeList(): void
    {
        $item = new ShopItem(['label' => 'T-shirt', 'attributes' => "S\nM"]);

        $this->assertTrue($item->hasAttributes());
        $this->assertSame(['S', 'M'], $item->getAttributeList());
        $this->assertFalse((new ShopItem(['label' => 'Cap']))->hasAttributes());
    }

    /**
     * The key of the form finds the attribute while it is offered
     */
    public function testAttributeFromKey(): void
    {
        $item = new ShopItem(['attributes' => "S\nM [kids]\nL"]);

        $this->assertSame('M [kids]', $item->attributeFromKey(ShopItem::attributeKey('M [kids]')));
        $this->assertNull($item->attributeFromKey(ShopItem::attributeKey('XL')));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{12}$/', ShopItem::attributeKey('M [kids]'));
    }

    public function testLabelWith(): void
    {
        $item = new ShopItem(['label' => 'T-shirt']);

        $this->assertSame('T-shirt - M', $item->labelWith('M'));
        $this->assertSame('T-shirt', $item->labelWith(null));
        $this->assertSame('T-shirt', $item->labelWith(''));
    }

    public function testToOrder(): void
    {
        $this->assertSame(3, (new ShopStock(['quantity' => -5, 'on_order' => 2]))->toOrder());
        $this->assertSame(0, (new ShopStock(['quantity' => -5, 'on_order' => 5]))->toOrder());
        $this->assertSame(0, (new ShopStock(['quantity' => 4, 'on_order' => 0]))->toOrder());
    }
}
