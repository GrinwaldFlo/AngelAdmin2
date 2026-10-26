<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSubventionCities extends AbstractMigration
{
    /**
     * Email address of the municipality of each city, to send the subsidy requests
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('subvention_cities')
            ->addColumn('city', 'string', ['limit' => 100])
            ->addColumn('email', 'string', ['limit' => 255, 'default' => ''])
            ->addIndex(['city'], ['unique' => true])
            ->create();
    }
}
