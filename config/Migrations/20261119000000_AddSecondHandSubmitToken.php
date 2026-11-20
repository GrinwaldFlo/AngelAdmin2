<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddSecondHandSubmitToken extends BaseMigration
{
    /**
     * Token of the form that created the item: the same form sent twice (double tap, browser retry) creates one item
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('second_hand_items')
            ->addColumn('submit_token', 'string', ['limit' => 36, 'null' => true, 'default' => null])
            ->addIndex(['profile_id', 'submit_token'], ['unique' => true])
            ->update();
    }
}
