<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MemberField1sTable;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\MemberField1sTable
 */
class MemberField1sTableTest extends TestCase
{
    protected array $fixtures = ['app.MemberField1s', 'app.Members'];

    private MemberField1sTable $MemberField1s;

    protected function setUp(): void
    {
        parent::setUp();
        $this->MemberField1s = $this->getTableLocator()->get('MemberField1s');
    }

    public function testValidationDefault(): void
    {
        $this->assertSame([], $this->MemberField1s->newEntity(['member_id' => 1, 'contact1_email' => 'a@example.com', 'contact2_email' => ''])->getErrors());

        $errors = $this->MemberField1s->newEntity([
            'contact1_email' => 'wrong', 'contact2_email' => 'wrong', 'facebook' => str_repeat('a', 256), 'a_connu_le_club_de' => str_repeat('a', 201),
        ])->getErrors();
        $this->assertSame(['facebook', 'contact1_email', 'contact2_email', 'a_connu_le_club_de'], array_keys($errors));
    }

    public function testSaveNeedsAnExistingMember(): void
    {
        $this->assertNotFalse($this->MemberField1s->save($this->MemberField1s->newEntity(['member_id' => 1, 'remarque' => 'Note'])));

        $fields = $this->MemberField1s->newEntity(['member_id' => 999]);
        $this->assertFalse($this->MemberField1s->save($fields));
        $this->assertArrayHasKey('member_id', $fields->getErrors());
    }
}
