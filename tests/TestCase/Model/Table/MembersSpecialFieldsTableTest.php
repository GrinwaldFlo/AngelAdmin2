<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MembersSpecialFieldsTable;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\MembersSpecialFieldsTable
 */
class MembersSpecialFieldsTableTest extends TestCase
{
    protected array $fixtures = ['app.MembersSpecialFields', 'app.Members'];

    private MembersSpecialFieldsTable $MembersSpecialFields;

    protected function setUp(): void
    {
        parent::setUp();
        $this->MembersSpecialFields = $this->getTableLocator()->get('MembersSpecialFields');
    }

    public function testValidationDefault(): void
    {
        $this->assertSame([], $this->MembersSpecialFields->newEntity(['member_id' => 1, 'special_field_id' => 1, 'value' => 'x'])->getErrors());
        $this->assertSame(['special_field_id', 'value'], array_keys($this->MembersSpecialFields->newEntity(['member_id' => 1])->getErrors()));
        $this->assertArrayHasKey('maxLength', $this->MembersSpecialFields->newEntity(['value' => str_repeat('a', 256)])->getError('value'));
    }

    public function testSaveNeedsAnExistingMember(): void
    {
        $this->assertNotFalse($this->MembersSpecialFields->save($this->MembersSpecialFields->newEntity(['member_id' => 1, 'special_field_id' => 1, 'value' => 'x'])));

        $field = $this->MembersSpecialFields->newEntity(['member_id' => 999, 'special_field_id' => 1, 'value' => 'x']);
        $this->assertFalse($this->MembersSpecialFields->save($field));
        $this->assertArrayHasKey('member_id', $field->getErrors());
    }
}
