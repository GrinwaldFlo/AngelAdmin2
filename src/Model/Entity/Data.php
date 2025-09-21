<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Data extends Entity
{
    // Make all fields mass assignable for simplicity
    protected array $_accessible = [
        '*' => true,
        'id' => true,
    ];
}