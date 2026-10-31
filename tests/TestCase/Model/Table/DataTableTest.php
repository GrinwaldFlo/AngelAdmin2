<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\DataTable;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\DataTable
 */
class DataTableTest extends TestCase
{
    protected array $fixtures = ['app.Data'];

    private DataTable $Data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Data = $this->getTableLocator()->get('Data');
    }

    public function testValidationDefault(): void
    {
        $this->assertSame([], $this->Data->newEntity(['param' => 1, 'data_type' => 'type', 'value' => 'x'])->getErrors());
        $this->assertSame(['param', 'data_type'], array_keys($this->Data->newEntity([])->getErrors()));
        $this->assertArrayHasKey('maxLength', $this->Data->newEntity(['value' => str_repeat('a', 1001)])->getError('value'));
        $this->assertArrayHasKey('integer', $this->Data->newEntity(['param' => 'a'])->getError('param'));
    }

    public function testSave(): void
    {
        // The SQLite test schema does not auto-increment the BIGINT id
        $data = $this->Data->newEntity(['param' => 1, 'data_type' => 'type', 'value' => 'x']);
        $data->id = 1;
        $this->assertNotFalse($this->Data->save($data));
        $this->assertSame(1, $this->Data->find()->where(['data_type' => 'type'])->count());
    }
}
