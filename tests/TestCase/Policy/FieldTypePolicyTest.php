<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\FieldType;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Policy\FieldTypePolicy
 */
class FieldTypePolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles'];

    /**
     * Rules not implemented yet must refuse, not fail
     */
    public function testUnimplementedRulesRefuse(): void
    {
        $user = $this->identity(['Admin', 'MemberViewAll', 'MemberEditAll', 'MemberEditOwn']);

        foreach (['create', 'update', 'delete', 'view'] as $action) {
            $this->assertFalse($user->can($action, new FieldType()), $action);
        }
    }

    public function testAdmin(): void
    {
        $this->assertTrue($this->identity(['Admin'])->can('admin', new FieldType()));
        $this->assertFalse($this->identity(['MemberEditAll'])->can('admin', new FieldType()));
    }
}
