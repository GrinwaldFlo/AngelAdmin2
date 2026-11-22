<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Maximum number of coaches with a presence type at one meeting of a team
 *
 * @property int $id
 * @property int $team_id
 * @property int $coach_presence_type_id
 * @property int $max_count
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Team $team
 * @property \App\Model\Entity\CoachPresenceType $coach_presence_type
 */
class CoachPresenceLimit extends Entity
{
    protected array $_accessible = [
        'max_count' => true,
    ];
}
