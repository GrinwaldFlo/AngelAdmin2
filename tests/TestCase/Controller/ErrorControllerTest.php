<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\Core\Configure;
use Cake\Log\Log;
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
        // The pages of production, whatever DEBUG in .env (restored by TestCase)
        Configure::write('debug', false);
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
        // Not a "page not found": the user learns the page exists but is not for this account
        $this->assertResponseContains('Access denied');
        $this->assertResponseNotContains('Page Not Found');
    }

    public function testForbiddenLoggedOnOneLine(): void
    {
        Log::setConfig('forbidden', ['className' => 'Array', 'levels' => ['warning', 'error']]);
        $this->loginWith([]);

        $this->get('/shop-items');

        $lines = Log::engine('forbidden')->read();
        Log::drop('forbidden');
        $this->assertCount(1, $lines);
        $this->assertStringStartsWith('warning: [403] ForbiddenException: /shop-items (user ', $lines[0]);
        $this->assertStringNotContainsString('Stack Trace', $lines[0]);
    }

    public function testForbiddenWithReasonShowsIt(): void
    {
        Log::setConfig('forbidden', ['className' => 'Array', 'levels' => ['warning', 'error']]);
        // Member link of member 100 on the page of member 1
        $this->addMember(100, [1]);
        $this->cookie('member_hash', sha1('member100'));

        $this->get('/members/edit/1');

        $lines = Log::engine('forbidden')->read();
        Log::drop('forbidden');
        $this->assertResponseCode(403);
        $this->assertTemplate('error400');
        // The reason given by the controller, translated, not a "page not found"
        $this->assertResponseContains(h(__('This link does not give access to this member.')));
        $this->assertResponseNotContains(h(__('Error 404 - Page Not Found')));
        $this->assertCount(1, $lines);
        $this->assertStringStartsWith('warning: [403] ForbiddenException: /members/edit/1', $lines[0]);
    }

    public function testOtherClientErrorShowsItsMessage(): void
    {
        $this->loginWith([]);

        // GET on a POST only action
        $this->get('/presences/set-presence/1');

        $this->assertResponseCode(405);
        $this->assertTemplate('error400');
        $this->assertResponseContains('Method Not Allowed');
        $this->assertResponseNotContains(h(__('Error 404 - Page Not Found')));
    }

    public function testExpiredFormShowsPageExpired(): void
    {
        // No CSRF token sent: like a form left open after the cookie expired
        $this->post('/users/forgot-password', ['email_or_username' => 'nobody']);

        $this->assertResponseCode(403);
        $this->assertTemplate('error400');
        $this->assertResponseContains('The page has expired');
    }
}
