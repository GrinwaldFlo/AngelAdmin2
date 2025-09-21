<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class MaintenanceMiddleware implements MiddlewareInterface
{
    public function process(\Psr\Http\Message\ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (Configure::read('App.maintenance')) {
            // If using CakePHP's ServerRequest methods, cast or check instance
            $clientIp = method_exists($request, 'clientIp') ? $request->clientIp() : $request->getServerParams()['REMOTE_ADDR'] ?? null;
            if ($clientIp != Configure::read('App.devIp')) {
                // Show maintenance page for all except allowed IPs
                $response = new Response();
                return $response->withStringBody(
                    '<html><head><title>Maintenance</title></head><body style="text-align:center;margin-top:10%"><h1>Site en maintenance</h1><p>Le site est actuellement en maintenance, merci de revenir plus tard.</p></body></html>'
                )->withStatus(503);
            } else {
                // Set a flag for allowed IPs to show banner (only if Cake\Http\ServerRequest)
                if (method_exists($request, 'withAttribute')) {
                    $request = $request->withAttribute('maintenanceBanner', true);
                }
            }
        }
        return $handler->handle($request);
    }
}