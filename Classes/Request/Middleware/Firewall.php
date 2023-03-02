<?php

declare(strict_types=1);
namespace In2code\T3AM\Request\Middleware;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use In2code\T3AM\Domain\Repository\ClientRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Firewall implements MiddlewareInterface
{
    /**
     * @throws DBALException|Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getQueryParams()['token'] ?? '';

        if (GeneralUtility::makeInstance(ClientRepository::class)->countByToken($token) !== 1) {
            $response = new JsonResponse();
            $response->setPayload(['code' => 1519999361, 'error' => true, 'message' => 'Access error', 'data' => []]);
            return $response;
        }

        return $handler->handle($request);
    }
}
