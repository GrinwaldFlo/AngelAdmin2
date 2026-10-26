<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContentsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ContentsController Test Case
 *
 * @uses \App\Controller\ContentsController
 */
class ContentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Contents',
        'app.Teams',
        'app.Sites',
        'app.Members',
        'app.Users',
        'app.Roles',
        'app.TeamsMembers',
        'app.Configurations',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)]);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $this->post('/contents/add', ['text' => 'Hello', 'location' => 12, 'url' => '', 'team_id' => '', 'sort' => 1]);
        $this->assertRedirect(['action' => 'index']);
        // No team is saved as 0, the column is not nullable
        $content = $this->getTableLocator()->get('Contents')->find()->where(['location' => 12])->firstOrFail();
        $this->assertSame(0, $content->team_id);
        $this->assertSame('Hello', $content->text);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $this->post('/contents/edit/1', ['text' => 'Changed', 'location' => 1, 'url' => '', 'team_id' => '', 'sort' => 0]);
        $this->assertRedirect(['action' => 'index']);
        $content = $this->getTableLocator()->get('Contents')->get(1);
        $this->assertSame(0, $content->team_id);
        $this->assertSame('Changed', $content->text);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
