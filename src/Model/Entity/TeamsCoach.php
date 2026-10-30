<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TeamsCoach Entity: a coach or help-coach of a team, both have the same access
 *
 * @property int $id
 * @property int $team_id
 * @property int $member_id
 * @property int $type self::COACH or self::HELP_COACH
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Team $team
 * @property \App\Model\Entity\Member $member
 */
class TeamsCoach extends Entity
{
    public const COACH = 1;
    public const HELP_COACH = 2;

    protected array $_accessible = [
        'team_id' => true,
        'member_id' => true,
        'type' => true,
    ];

    /**
     * @return array<int, string> label by type
     */
    public static function types(): array
    {
        return [
            self::COACH => __('Coach'),
            self::HELP_COACH => __('Help coach'),
        ];
    }
}
