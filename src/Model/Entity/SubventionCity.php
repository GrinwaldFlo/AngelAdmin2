<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SubventionCity Entity: email address of the municipality of a city
 *
 * @property int $id
 * @property string $city same value as Members.city
 * @property string $email
 */
class SubventionCity extends Entity
{
    protected array $_accessible = [
        'city' => true,
        'email' => true,
    ];
}
