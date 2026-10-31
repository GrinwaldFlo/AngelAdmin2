<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestSuite\CrudControllerTestCase;

/**
 * @uses \App\Controller\RolesController
 */
class RolesControllerTest extends CrudControllerTestCase
{
    protected string $url = '/roles';

    protected string $table = 'Roles';

    protected string $field = 'name';

    protected bool $canDelete = false;

    protected function validData(): array
    {
        return ['name' => 'Coach', 'MemberViewAll' => 1, 'MemberEditAll' => 0, 'MemberEditOwn' => 1, 'BillViewAll' => 0, 'BillEditAll' => 0, 'BillValidate' => 0, 'Editor' => 0, 'Admin' => 0];
    }
}
