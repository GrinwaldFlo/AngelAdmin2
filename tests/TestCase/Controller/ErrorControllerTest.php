<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Error pages are rendered by the ErrorController, logged in or not
 *
 * @uses \App\Controller\ErrorController
 */
class ErrorControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.ShopItems', 'app.MemberOrders',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
    }

    public function testMissingControllerNotLogged(): void
    {
        $this->get('/no-such-controller');

        $this->assertResponseCode(404);
        $this->assertTemplate('error400');
    }

    public function testMissingRecordLogged(): void
    {
        $this->loginWith(['MemberEditAll']);

        $this->get('/shop-items/view/999');

        $this->assertResponseCode(404);
        $this->assertTemplate('error400');
    }

    public function testForbidden(): void
    {
        $this->loginWith([]);

        $this->get('/shop-items');

        $this->assertResponseCode(403);
        $this->assertTemplate('error400');
    }
}
