<?php

declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\MaintenanceMiddleware;
use App\Test\TestSuite\CallbackRequestHandler;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @uses \App\Middleware\MaintenanceMiddleware
 */
class MaintenanceMiddlewareTest extends TestCase
{
    private ?ServerRequestInterface $handled = null;

    private function process(string $ip): ResponseInterface
    {
        $this->handled = null;
        $handler = new CallbackRequestHandler(function (ServerRequestInterface $request) {
            $this->handled = $request;

            return new Response(['body' => 'app']);
        });

        return (new MaintenanceMiddleware())->process(new ServerRequest(['environment' => ['REMOTE_ADDR' => $ip]]), $handler);
    }

    public function testNotInMaintenance(): void
    {
        Configure::write('App.maintenance', false);

        $response = $this->process('1.2.3.4');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('app', (string)$response->getBody());
        $this->assertNull($this->handled->getAttribute('maintenanceBanner'));
    }

    public function testMaintenancePageForVisitors(): void
    {
        Configure::write('App.maintenance', true);
        Configure::write('App.devIp', '10.0.0.1');

        $response = $this->process('1.2.3.4');

        $this->assertSame(503, $response->getStatusCode());
        $this->assertStringContainsString('maintenance', (string)$response->getBody());
        $this->assertNull($this->handled);
    }

    public function testDeveloperIpGetsTheAppWithBanner(): void
    {
        Configure::write('App.maintenance', true);
        Configure::write('App.devIp', '10.0.0.1');

        $response = $this->process('10.0.0.1');

        $this->assertSame('app', (string)$response->getBody());
        $this->assertTrue($this->handled->getAttribute('maintenanceBanner'));
    }
}
