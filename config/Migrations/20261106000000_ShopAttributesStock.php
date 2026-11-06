<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class ShopAttributesStock extends BaseMigration
{
    /**
     * Shop by city, with customizable attributes (e.g. sizes), stock by attribute and the merchandising manager right
     *
     * - shop_items.site_id: city of the item (the existing items belong to the site 1)
     * - shop_items.attributes: one possible attribute per line, empty = no attribute to choose
     * - shop_items.allow_out_of_stock: the item can be ordered without stock (set for the existing items, they have no stock yet)
     * - member_orders.attribute: copy of the chosen attribute (no link: it may be removed from the list later)
     * - shop_stocks: stock of each item and attribute ('' = item without attribute), negative = to order
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('shop_items')
            ->addColumn('site_id', 'integer', ['default' => 1])
            ->addColumn('attributes', 'text', ['null' => true])
            ->addColumn('allow_out_of_stock', 'boolean', ['default' => false])
            ->addIndex(['site_id'])
            ->addForeignKey('site_id', 'sites', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->update();
        // The existing items have no stock: they stay orderable as before
        $this->execute('UPDATE shop_items SET allow_out_of_stock = 1');

        $this->table('member_orders')
            ->addColumn('attribute', 'string', ['limit' => 100, 'null' => true])
            ->update();

        $this->table('shop_stocks')
            ->addColumn('shop_item_id', 'integer')
            ->addColumn('attribute', 'string', ['limit' => 100, 'default' => ''])
            ->addColumn('quantity', 'integer', ['default' => 0])
            ->addColumn('on_order', 'integer', ['default' => 0])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['shop_item_id', 'attribute'], ['unique' => true])
            ->addForeignKey('shop_item_id', 'shop_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        $this->table('roles')
            ->addColumn('ShopManager', 'boolean', ['default' => false])
            ->update();
    }

    public function down(): void
    {
        $this->table('roles')->removeColumn('ShopManager')->update();
        $this->table('shop_stocks')->drop()->save();
        $this->table('member_orders')->removeColumn('attribute')->update();
        $this->table('shop_items')
            ->dropForeignKey('site_id')
            ->removeIndex(['site_id'])
            ->removeColumn('site_id')
            ->removeColumn('attributes')
            ->removeColumn('allow_out_of_stock')
            ->update();
    }
}
