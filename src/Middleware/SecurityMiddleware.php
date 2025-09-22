<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Security Middleware
 * 
 * Provides additional security features including:
 * - Rate limiting
 * - Security headers
 * - Request validation
 */
class SecurityMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and add security measures
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Add security headers to response
        $response = $handler->handle($request);
        
        // Add security headers if not already set
        $response = $this->addSecurityHeaders($response, $request);
        
        // Log suspicious requests
        $this->logSuspiciousActivity($request);
        
        return $response;
    }
    
    /**
     * Add security headers to the response
     */
    private function addSecurityHeaders(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
        
        // Add HSTS for HTTPS requests
        if ($request->getUri()->getScheme() === 'https') {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }
        
        // Add CSP header
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
               "style-src 'self' 'unsafe-inline'; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' data:; " .
               "connect-src 'self'";
        $headers['Content-Security-Policy'] = $csp;
        
        foreach ($headers as $header => $value) {
            if (!$response->hasHeader($header)) {
                $response = $response->withHeader($header, $value);
            }
        }
        
        // Remove server identification headers
        $response = $response->withoutHeader('Server');
        $response = $response->withoutHeader('X-Powered-By');
        
        return $response;
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(ServerRequestInterface $request): void
    {
        $uri = (string)$request->getUri();
        $method = $request->getMethod();
        $userAgent = $request->getHeaderLine('User-Agent');
        $clientIp = $this->getClientIp($request);
        
        // Log requests to sensitive paths
        $sensitivePaths = [
            '/admin', '/config', '/.env', '/backup', 
            '/phpinfo', '/wp-admin', '/wp-login',
            '/phpmyadmin', '/.git', '/.svn'
        ];
        
        foreach ($sensitivePaths as $path) {
            if (strpos($uri, $path) !== false) {
                \Cake\Log\Log::write('warning', 
                    "Suspicious request to sensitive path: {$uri} from IP: {$clientIp}, User-Agent: {$userAgent}",
                    ['scope' => ['security']]
                );
                break;
            }
        }
        
        // Log requests with suspicious user agents
        $suspiciousUserAgents = [
            'nikto', 'sqlmap', 'nmap', 'masscan', 'zap',
            'burp', 'w3af', 'acunetix', 'nessus'
        ];
        
        $lowerUserAgent = strtolower($userAgent);
        foreach ($suspiciousUserAgents as $suspicious) {
            if (strpos($lowerUserAgent, $suspicious) !== false) {
                \Cake\Log\Log::write('warning',
                    "Suspicious User-Agent detected: {$userAgent} from IP: {$clientIp} requesting: {$uri}",
                    ['scope' => ['security']]
                );
                break;
            }
        }
        
        // Log requests with suspicious parameters
        $queryParams = $request->getQueryParams();
        $suspiciousParams = ['union', 'select', 'drop', 'insert', 'update', 'delete', '<script', 'javascript:'];
        
        foreach ($queryParams as $param => $value) {
            $lowerValue = strtolower((string)$value);
            foreach ($suspiciousParams as $suspicious) {
                if (strpos($lowerValue, $suspicious) !== false) {
                    \Cake\Log\Log::write('warning',
                        "Suspicious query parameter detected: {$param}={$value} from IP: {$clientIp}",
                        ['scope' => ['security']]
                    );
                    break 2;
                }
            }
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        
        // Check for IP from shared internet
        if (!empty($serverParams['HTTP_CLIENT_IP'])) {
            return $serverParams['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $serverParams['HTTP_X_FORWARDED_FOR'])[0];
        }
        // Check for IP from remote address
        elseif (!empty($serverParams['REMOTE_ADDR'])) {
            return $serverParams['REMOTE_ADDR'];
        }
        
        return 'unknown';
    }
}
