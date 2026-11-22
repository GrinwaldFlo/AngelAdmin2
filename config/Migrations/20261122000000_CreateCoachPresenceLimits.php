<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCoachPresenceLimits extends BaseMigration
{
    /**
     * Coach payment module: maximum number of coaches with a presence type at one meeting of a team
     * No row = no limit
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('coach_presence_limits')
            ->addColumn('team_id', 'integer')
            ->addColumn('coach_presence_type_id', 'integer')
            ->addColumn('max_count', 'integer', ['default' => 0])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['team_id', 'coach_presence_type_id'], ['unique' => true])
            ->addIndex(['coach_presence_type_id'])
            ->addForeignKey('team_id', 'teams', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('coach_presence_type_id', 'coach_presence_types', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('coach_presence_limits')->drop()->save();
    }
}
