<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\BillTemplate;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Policy\BillTemplatePolicy
 */
class BillTemplatePolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles'];

    public function testCanAdminOnlyWithAdmin(): void
    {
        $resource = new BillTemplate();

        $this->assertTrue($this->identity(['Admin'])->can('admin', $resource));
        $this->assertFalse($this->identity(['MemberViewAll', 'MemberEditAll', 'BillViewAll', 'BillEditAll'])->can('admin', $resource));
    }
}
