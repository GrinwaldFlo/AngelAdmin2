<?php

declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Content;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Policy\ContentPolicy
 */
class ContentPolicyTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = ['app.Members', 'app.Users', 'app.Roles'];

    public function testCanEditorOnlyWithEditor(): void
    {
        $resource = new Content();

        $this->assertTrue($this->identity(['Editor'])->can('editor', $resource));
        $this->assertFalse($this->identity(['MemberViewAll', 'MemberEditAll', 'BillViewAll', 'BillEditAll'])->can('editor', $resource));
    }
}
