<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddTeamsCoaches extends AbstractMigration
{
    /**
     * Coaches and help-coaches of each team, the access of a TeamScoped role is limited to these teams
     * Replaces the members.coach flag
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('teams_coaches')
            ->addColumn('team_id', 'integer')
            ->addColumn('member_id', 'integer')
            ->addColumn('type', 'integer', ['default' => 1])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['team_id', 'member_id'], ['unique' => true])
            ->addIndex(['member_id'])
            ->addForeignKey('team_id', 'teams', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('member_id', 'members', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('roles')
            ->addColumn('TeamScoped', 'boolean', ['default' => false])
            ->update();

        $this->table('members')
            ->removeColumn('coach')
            ->update();
    }

    public function down(): void
    {
        $this->table('members')
            ->addColumn('coach', 'boolean', ['default' => false])
            ->update();

        $this->table('roles')
            ->removeColumn('TeamScoped')
            ->update();

        $this->table('teams_coaches')->drop()->save();
    }
}
