<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SecondHandCategory Entity: category of the second-hand items, managed by the admin
 *
 * @property int $id
 * @property string $name
 */
class SecondHandCategory extends Entity
{
    protected array $_accessible = [
        'name' => true,
    ];
}
