<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestSuite\CrudControllerTestCase;

/**
 * @uses \App\Controller\ConfigurationsController
 */
class ConfigurationsControllerTest extends CrudControllerTestCase
{
    protected string $url = '/configurations';

    protected string $table = 'Configurations';

    protected string $field = 'value';

    protected function validData(): array
    {
        return ['label' => 'clubName', 'value' => 'My club'];
    }
}
