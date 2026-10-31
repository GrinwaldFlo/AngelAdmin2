<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestSuite\CrudControllerTestCase;

/**
 * @uses \App\Controller\FieldTypesController
 */
class FieldTypesControllerTest extends CrudControllerTestCase
{
    protected string $url = '/field-types';

    protected string $table = 'FieldTypes';

    protected string $field = 'label';

    protected function validData(): array
    {
        return ['label' => 'Allergies', 'style' => 0, 'member_edit' => 1, 'sort' => 3, 'hidden' => 0, 'mandatory' => 0];
    }
}
