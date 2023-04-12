<?php

declare(strict_types=1);

namespace In2code\T3AM\Request\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

use function is_string;

class Router implements MiddlewareInterface
{
    public function __construct(
        protected array $routes)
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getQueryParams()['route'] ?? null;

        if (!is_string($route) || !isset($this->routes[$route])) {
            $response = new JsonResponse();
            $response->setPayload(['code' => 1496395045, 'error' => true, 'message' => 'Routing error', 'data' => []]);
            return $response;
        }

        $request = $request->withAttribute('route', $this->routes[$route]);

        return $handler->handle($request);
    }
}
