<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ContentsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ContentsTable Test Case
 */
class ContentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ContentsTable
     */
    protected $Contents;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Contents',
        'app.Teams',
        'app.Sites',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Contents') ? [] : ['className' => ContentsTable::class];
        $this->Contents = TableRegistry::getTableLocator()->get('Contents', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Contents);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Contents->newEntity([
            'text' => 'Valid text',
            'location' => '',
            'url' => str_repeat('a', 1001),
            'sort' => '',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('location', $errors);
        $this->assertArrayHasKey('url', $errors);
        $this->assertArrayHasKey('sort', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $entity = $this->Contents->newEntity([
            'text' => 'Valid content',
            'location' => 1,
            'url' => 'https://example.com',
            'sort' => 10,
            'team_id' => 999999,
        ]);

        $this->assertTrue($this->Contents->checkRules($entity));
        $this->assertEmpty($entity->getErrors());
    }
}
