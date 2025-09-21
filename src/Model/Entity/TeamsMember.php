<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TeamsMember Entity
 *
 * @property int $team_id
 * @property int $member_id
 *
 * @property \App\Model\Entity\Team $team
 * @property \App\Model\Entity\Member $member
 */
class TeamsMember extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'team' => true,
        'member' => true,
    ];
}
