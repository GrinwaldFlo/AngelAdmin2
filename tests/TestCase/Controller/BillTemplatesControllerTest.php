<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestSuite\CrudControllerTestCase;

/**
 * @uses \App\Controller\BillTemplatesController
 */
class BillTemplatesControllerTest extends CrudControllerTestCase
{
    protected string $url = '/bill-templates';

    protected string $table = 'BillTemplates';

    protected string $field = 'label';

    protected function validData(): array
    {
        return ['label' => 'Shirt', 'amount' => 30, 'membership_fee' => 0, 'site_id' => 1];
    }
}
