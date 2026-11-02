<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSecondHand extends BaseMigration
{
    /**
     * Second-hand items between members:
     * - profiles: terms accepted by a member, and its seller details
     * - items: items for sale, with one picture
     * - logs: copy of the text of each item, kept when the item is deleted
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('second_hand_categories')
            ->addColumn('name', 'string', ['limit' => 100])
            ->create();

        $this->table('second_hand_profiles')
            ->addColumn('member_id', 'integer')
            ->addColumn('terms_accepted', 'datetime', ['null' => true])
            ->addColumn('name', 'string', ['limit' => 100, 'default' => ''])
            ->addColumn('phone', 'string', ['limit' => 30, 'default' => ''])
            ->addColumn('whatsapp', 'boolean', ['default' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'default' => ''])
            ->addColumn('email_verified', 'boolean', ['default' => false])
            ->addColumn('email_token', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('city', 'string', ['limit' => 100, 'default' => ''])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['member_id'], ['unique' => true])
            ->addIndex(['email_token'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('second_hand_items')
            ->addColumn('profile_id', 'integer')
            ->addColumn('category_id', 'integer')
            ->addColumn('description', 'text')
            ->addColumn('size', 'string', ['limit' => 50])
            ->addColumn('price', 'decimal', ['precision' => 8, 'scale' => 2])
            ->addColumn('hash', 'string', ['limit' => 40])
            ->addColumn('sold', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['created'])
            ->addForeignKey('profile_id', 'second_hand_profiles', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('category_id', 'second_hand_categories', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('second_hand_logs')
            ->addColumn('item_id', 'integer')
            ->addColumn('member_id', 'integer', ['null' => true])
            ->addColumn('seller_name', 'string', ['limit' => 100])
            ->addColumn('category', 'string', ['limit' => 100])
            ->addColumn('description', 'text')
            ->addColumn('size', 'string', ['limit' => 50])
            ->addColumn('price', 'decimal', ['precision' => 8, 'scale' => 2])
            ->addColumn('status', 'integer', ['default' => 0])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('closed', 'datetime', ['null' => true])
            ->addIndex(['item_id'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
