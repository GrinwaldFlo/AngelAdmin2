<?php

declare(strict_types=1);

namespace App\Policy;

use Cake\ORM\TableRegistry;

/**
 * Access of a TeamScoped role (coaches): limited to the members, meetings and bills of the teams it coaches
 * Shared by the policies and AppController
 */
class TeamScope
{
    /**
     * @param \Authorization\IdentityInterface|\App\Model\Entity\User|null $user
     */
    public static function isScoped(?object $user): bool
    {
        return $user !== null && $user->Role->isScoped();
    }

    /**
     * Teams the user can access
     * @param \Authorization\IdentityInterface|\App\Model\Entity\User|null $user
     * @return int[]|null null = all teams, [] = none (coach without team)
     */
    public static function teamIds(?object $user): ?array
    {
        if (!self::isScoped($user)) {
            return null;
        }

        return TableRegistry::getTableLocator()->get('TeamsCoaches')->teamIdsOf((int)$user->member_id);
    }

    /**
     * @param \Authorization\IdentityInterface|\App\Model\Entity\User|null $user
     */
    public static function canAccessTeam(?object $user, ?int $teamId): bool
    {
        $teamIds = self::teamIds($user);

        return $teamIds === null || in_array((int)$teamId, $teamIds, true);
    }

    /**
     * True if every team is accessible (and there is at least one)
     * @param \Authorization\IdentityInterface|\App\Model\Entity\User|null $user
     * @param int[] $teamIds
     */
    public static function canAccessAllTeams(?object $user, array $teamIds): bool
    {
        $allowed = self::teamIds($user);
        if ($allowed === null) {
            return true;
        }

        return !empty($teamIds) && empty(array_diff($teamIds, $allowed));
    }

    /**
     * Active member in one of the accessible teams (coaches do not see the old members)
     * @param \Authorization\IdentityInterface|\App\Model\Entity\User|null $user
     */
    public static function canAccessMember(?object $user, ?int $memberId): bool
    {
        return TableRegistry::getTableLocator()->get('Members')->isActiveInTeams($memberId, self::teamIds($user));
    }
}
