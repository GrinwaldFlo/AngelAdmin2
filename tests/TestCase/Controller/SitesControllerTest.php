<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\SitesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SitesController Test Case
 *
 * @uses \App\Controller\SitesController
 */
class SitesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Sites',
    ];

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->get('/sites');
        $this->assertResponseCode(302);
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->get('/sites/view/1');
        $this->assertResponseCode(302);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $this->get('/sites/add');
        $this->assertResponseCode(302);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $this->get('/sites/edit/1');
        $this->assertResponseCode(302);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->post('/sites/delete/1');
        $this->assertResponseCode(302);
    }
}
