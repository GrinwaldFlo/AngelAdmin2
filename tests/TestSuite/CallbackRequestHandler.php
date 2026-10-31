<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Next handler of a tested middleware: returns the response built by the callback
 */
class CallbackRequestHandler implements RequestHandlerInterface
{
    public function __construct(private Closure $callback)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->callback)($request);
    }
}
