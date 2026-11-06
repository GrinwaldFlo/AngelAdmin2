<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddMemberSite extends BaseMigration
{
    /**
     * City of the member, stored on the member instead of being deduced from its teams
     * Existing members get the city of their active teams (most active teams, then newest team), the ones without team get the site 1
     * The memberships of the teams of another city are removed (not restored by down())
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('members')
            ->addColumn('site_id', 'integer', ['default' => 1, 'null' => false])
            ->update();

        // City where the member has the most active teams, the one of its newest team on a tie (or without active team)
        $this->execute('UPDATE members SET site_id = COALESCE((
            SELECT teams.site_id FROM teams_members
            INNER JOIN teams ON teams.id = teams_members.team_id
            WHERE teams_members.member_id = members.id
            GROUP BY teams.site_id
            ORDER BY SUM(CASE WHEN teams.active = 1 THEN 1 ELSE 0 END) DESC, MAX(teams.id) DESC
            LIMIT 1), 1)');

        // One city per member: it leaves the teams of the other cities
        $this->execute('DELETE FROM teams_members WHERE EXISTS (
            SELECT 1 FROM teams INNER JOIN members ON members.id = teams_members.member_id
            WHERE teams.id = teams_members.team_id AND teams.site_id <> members.site_id)');

        $this->table('members')
            ->addForeignKey('site_id', 'sites', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->update();
    }

    public function down(): void
    {
        $this->table('members')
            ->dropForeignKey('site_id')
            ->removeColumn('site_id')
            ->update();
    }
}
