<?php

declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\SecurityMiddleware;
use App\Test\TestSuite\CallbackRequestHandler;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Middleware\SecurityMiddleware
 */
class SecurityMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::setConfig('securityTest', ['className' => 'Array', 'scopes' => ['security'], 'levels' => ['warning']]);
    }

    protected function tearDown(): void
    {
        Log::drop('securityTest');
        parent::tearDown();
    }

    private function process(array $request, ?Response $response = null): Response
    {
        $handler = new CallbackRequestHandler(fn () => $response ?? new Response());

        return (new SecurityMiddleware())->process(new ServerRequest($request), $handler);
    }

    /**
     * @return string[] security warnings logged
     */
    private function logs(): array
    {
        return Log::engine('securityTest')->read();
    }

    public function testSecurityHeaders(): void
    {
        $response = $this->process(['url' => '/members']);

        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertStringContainsString("default-src 'self'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertStringContainsString('https://fonts.googleapis.com', $response->getHeaderLine('Content-Security-Policy'));
        $this->assertFalse($response->hasHeader('Strict-Transport-Security'));
    }

    public function testHstsOnlyForHttps(): void
    {
        $response = $this->process(['url' => '/members', 'environment' => ['HTTPS' => 'on']]);

        $this->assertStringStartsWith('max-age=', $response->getHeaderLine('Strict-Transport-Security'));
    }

    public function testKeepsHeadersSetByTheAppAndRemovesServerHeaders(): void
    {
        $appResponse = (new Response())
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Server', 'Apache')
            ->withHeader('X-Powered-By', 'PHP');

        $response = $this->process(['url' => '/'], $appResponse);

        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertFalse($response->hasHeader('Server'));
        $this->assertFalse($response->hasHeader('X-Powered-By'));
    }

    public function testNormalRequestIsNotLogged(): void
    {
        $this->process(['url' => '/members/view/1', 'query' => ['page' => '2'], 'environment' => ['HTTP_USER_AGENT' => 'Mozilla/5.0']]);

        $this->assertSame([], $this->logs());
    }

    public function testLogsSensitivePath(): void
    {
        $this->process(['url' => '/.env', 'environment' => ['REMOTE_ADDR' => '10.0.0.1']]);

        $this->assertCount(1, $this->logs());
        $this->assertStringContainsString('sensitive path', $this->logs()[0]);
        $this->assertStringContainsString('10.0.0.1', $this->logs()[0]);
    }

    public function testLogsSuspiciousUserAgentWithForwardedIp(): void
    {
        $this->process(['url' => '/', 'environment' => ['HTTP_USER_AGENT' => 'sqlmap/1.7', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 10.0.0.1']]);

        $this->assertCount(1, $this->logs());
        $this->assertStringContainsString('User-Agent', $this->logs()[0]);
        $this->assertStringContainsString('1.2.3.4', $this->logs()[0]);
        $this->assertStringNotContainsString('10.0.0.1', $this->logs()[0]);
    }

    public function testLogsSuspiciousQueryOnce(): void
    {
        $this->process(['url' => '/', 'query' => ['q' => '1 UNION SELECT password', 'r' => '<script>']]);

        $this->assertCount(1, $this->logs());
        $this->assertStringContainsString('q=1 UNION SELECT password', $this->logs()[0]);
    }

    public function testArrayQueryParameters(): void
    {
        $this->process(['url' => '/', 'query' => ['ids' => ['1', '2']]]);
        $this->assertSame([], $this->logs());

        $this->process(['url' => '/', 'query' => ['filter' => ['name' => '<script>alert(1)</script>']]]);
        $this->assertCount(1, $this->logs());
    }
}
