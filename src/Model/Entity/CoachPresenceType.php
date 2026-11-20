<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Presence type of a coach at a meeting (e.g. Main 10.-), defined per site
 *
 * @property int $id
 * @property int $site_id
 * @property string $label
 * @property string|float $amount CHF paid to the coach
 * @property int $sort
 * @property bool $active false = hidden from the attendance page, kept in the reports
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Site $site
 */
class CoachPresenceType extends Entity
{
    protected array $_accessible = [
        'label' => true,
        'amount' => true,
        'sort' => true,
        'active' => true,
    ];
}
