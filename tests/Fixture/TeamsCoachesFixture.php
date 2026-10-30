<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TeamsCoachesFixture: no coach by default, tests add the ones they need
 */
class TeamsCoachesFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
