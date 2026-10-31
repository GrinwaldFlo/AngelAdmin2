<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\TeamsCoach;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Entity\TeamsCoach
 */
class TeamsCoachTest extends TestCase
{
    public function testTypes(): void
    {
        $this->assertSame([TeamsCoach::COACH, TeamsCoach::HELP_COACH], array_keys(TeamsCoach::types()));
    }
}
