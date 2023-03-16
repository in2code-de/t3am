<?php

declare(strict_types=1);

namespace In2code\T3AM\Request;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_reverse;
use function class_exists;
use function is_object;
use function is_string;

class RequestHandler implements RequestHandlerInterface
{
    protected MiddlewareInterface $middleware;

    protected RequestHandlerInterface $requestHandler;

    public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $requestHandler)
    {
        $this->middleware = $middleware;
        $this->requestHandler = $requestHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->requestHandler);
    }

    /**
     * @throws Exception
     */
    public static function fromMiddlewareStack(
        RequestHandlerInterface $defaultRequestHandler,
        array $middlewareStack
    ): RequestHandlerInterface {
        $requestHandler = $defaultRequestHandler;
        foreach (array_reverse($middlewareStack) as $middleware) {
            if (is_string($middleware) && class_exists($middleware)) {
                $middleware = GeneralUtility::makeInstance($middleware);
            }
            if (!is_object($middleware)) {
                throw new Exception('Middleware "' . $middleware . '" must be an object or instantiatable class');
            }
            $requestHandler = GeneralUtility::makeInstance(static::class, $middleware, $requestHandler);
        }
        return $requestHandler;
    }
}
