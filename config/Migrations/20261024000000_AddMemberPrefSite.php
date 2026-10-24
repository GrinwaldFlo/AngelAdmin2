<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddMemberPrefSite extends AbstractMigration
{
    /**
     * Site (city) chosen by the member in the menu, 0 = all sites
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('members')
            ->addColumn('pref_site_id', 'integer', ['default' => 0])
            ->update();
    }
}
