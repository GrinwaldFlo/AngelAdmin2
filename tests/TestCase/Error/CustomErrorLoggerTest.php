<?php

declare(strict_types=1);

namespace App\Test\TestCase\Error;

use App\Error\CustomErrorLogger;
use Cake\Controller\Exception\MissingActionException;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * @uses \App\Error\CustomErrorLogger
 */
class CustomErrorLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::setConfig('errorLoggerTest', ['className' => 'Array', 'levels' => ['error']]);
    }

    protected function tearDown(): void
    {
        Log::drop('errorLoggerTest');
        putenv('ENABLE_SIMPLIFIED_404_LOGGING');
        parent::tearDown();
    }

    private function logs(): array
    {
        return Log::engine('errorLoggerTest')->read();
    }

    public function testNotFoundIsOneLine(): void
    {
        $logger = new CustomErrorLogger();
        $request = new ServerRequest(['url' => '/wp-login.php']);

        $logger->logException(new NotFoundException('Not found'), $request);
        $logger->logException(new MissingControllerException(['controller' => 'Foo']), $request);
        $logger->logException(new MissingActionException(['controller' => 'Foo', 'action' => 'bar']), $request);

        $logs = $this->logs();
        $this->assertCount(3, $logs);
        $this->assertStringContainsString('[404] NotFoundException: /wp-login.php', $logs[0]);
        $this->assertStringContainsString('[404] MissingControllerException', $logs[1]);
        $this->assertStringContainsString('[404] MissingActionException', $logs[2]);
        $this->assertStringNotContainsString('Stack Trace', implode('', $logs));
    }

    public function testOtherErrorsKeepTheDefaultLog(): void
    {
        (new CustomErrorLogger())->logException(new RuntimeException('Boom'), new ServerRequest(['url' => '/members']));

        $this->assertCount(1, $this->logs());
        $this->assertStringContainsString('[RuntimeException] Boom', $this->logs()[0]);
        $this->assertStringContainsString('Request URL: /members', $this->logs()[0]);
    }

    public function testNotFoundWithoutRequestKeepsTheDefaultLog(): void
    {
        (new CustomErrorLogger())->logException(new NotFoundException('Not found'));

        $this->assertStringContainsString('[Cake\Http\Exception\NotFoundException] Not found', $this->logs()[0]);
    }

    public function testSimplifiedLoggingCanBeDisabled(): void
    {
        putenv('ENABLE_SIMPLIFIED_404_LOGGING=0');

        (new CustomErrorLogger())->logException(new NotFoundException('Not found'), new ServerRequest(['url' => '/x']));

        $this->assertStringNotContainsString('[404]', $this->logs()[0]);
    }
}
