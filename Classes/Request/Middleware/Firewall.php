<?php
declare(strict_types=1);
namespace In2code\T3AM\Request\Middleware;

use In2code\T3AM\Server\SecurityService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Firewall implements MiddlewareInterface
{
    /** @var SecurityService */
    protected $tokenService = null;

    public function __construct()
    {
        $this->tokenService = GeneralUtility::makeInstance(SecurityService::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getQueryParams()['token'] ?? '';

        if (!$this->tokenService->isValid($token)) {
            $response = new JsonResponse();
            $response->setPayload(['code' => 1519999361, 'error' => true, 'message' => 'Access error', 'data' => []]);
            return $response;
        }

        return $handler->handle($request);
    }
}
