<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Payment of a site to a coach: covers the presences linked to it
 *
 * @property int $id
 * @property int $member_id
 * @property int $site_id
 * @property string|float $amount CHF
 * @property \Cake\I18n\Date $paid_date
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Member $member
 * @property \App\Model\Entity\Site $site
 * @property \App\Model\Entity\CoachPresence[] $coach_presences
 */
class CoachPayment extends Entity
{
    protected array $_accessible = [];
}
