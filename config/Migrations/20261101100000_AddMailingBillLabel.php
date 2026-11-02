<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddMailingBillLabel extends BaseMigration
{
    /**
     * Mailings to the members having a bill whose label contains a text: the searched text
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('mailings')
            ->addColumn('bill_label', 'string', ['limit' => 200, 'default' => ''])
            ->update();
    }
}
