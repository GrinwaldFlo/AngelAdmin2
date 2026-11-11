<?php
declare(strict_types=1);

namespace App\Error;

use Authorization\Exception\ForbiddenException;
use Cake\Error\ErrorLogger;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Exception\MissingControllerException;
use Cake\Controller\Exception\MissingActionException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Custom Error Logger
 * 
 * Reduces verbose logging for all 404 errors to single-line entries
 * Refused accesses (403) are expected (old links, bookmarks, expired forms): one warning line, without trace
 */
class CustomErrorLogger extends ErrorLogger
{
    /**
     * Log an exception with simplified format for all 404 errors
     *
     * @param \Throwable $exception Exception to log
     * @param \Psr\Http\Message\ServerRequestInterface|null $request Request data
     * @param bool $includeTrace Whether to include stack trace
     * @return void
     */
    public function logException(Throwable $exception, ?ServerRequestInterface $request = null, bool $includeTrace = false): void
    {
        // Check if simplified logging is enabled (can be disabled via environment variable)
        $enableSimplifiedLogging = env('ENABLE_SIMPLIFIED_404_LOGGING', true);
        
        if ($enableSimplifiedLogging && $this->is404Exception($exception) && $request !== null) {
            $requestUri = $request->getRequestTarget();
            $this->logSimplified404($exception, $requestUri);
            return;
        }

        if ($this->is403Exception($exception) && $request !== null) {
            $this->logSimplified403($exception, $request);
            return;
        }

        // For all other exceptions, use the default logging behavior
        parent::logException($exception, $request, $includeTrace);
    }

    /**
     * Check if this is a 404-type exception
     *
     * @param \Throwable $exception The exception to check
     * @return bool
     */
    protected function is404Exception(Throwable $exception): bool
    {
        return $exception instanceof MissingControllerException
            || $exception instanceof MissingActionException
            || $exception instanceof NotFoundException;
    }

    /**
     * Check if this is a refused access: no right on the page, or expired form
     *
     * @param \Throwable $exception The exception to check
     * @return bool
     */
    protected function is403Exception(Throwable $exception): bool
    {
        return $exception instanceof ForbiddenException
            || $exception instanceof InvalidCsrfTokenException;
    }

    /**
     * Log a refused access on one line, with the user to know who had the problem
     *
     * @param \Throwable $exception The exception
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return void
     */
    protected function logSimplified403(Throwable $exception, ServerRequestInterface $request): void
    {
        $identity = $request->getAttribute('identity');
        $user = $identity !== null ? 'user ' . $identity->id : 'not logged';

        $message = sprintf(
            '[403] %s: %s (%s) %s',
            (new \ReflectionClass($exception))->getShortName(),
            $request->getRequestTarget(),
            $user,
            $exception->getMessage()
        );

        Log::write('warning', $message);
    }

    /**
     * Log a simplified version of the 404 error
     *
     * @param \Throwable $exception The exception
     * @param string $requestUri The request URI
     * @return void
     */
    protected function logSimplified404(Throwable $exception, string $requestUri): void
    {
        $exceptionName = (new \ReflectionClass($exception))->getShortName();
        
        $message = sprintf(
            '[404] %s: %s',
            $exceptionName,
            $requestUri
        );

        // Use Log::write instead of accessing $_logger directly
        Log::write('error', $message);
    }
}
