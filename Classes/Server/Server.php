<?php

declare(strict_types=1);

namespace In2code\T3AM\Server;

/*
 * Copyright (C) 2018 Oliver Eglseder <php@vxvr.de>, in2code GmbH
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

use Exception;
use In2code\T3AM\Request\Middleware\Firewall;
use In2code\T3AM\Request\Middleware\Router;
use In2code\T3AM\Request\RequestDispatcher;
use In2code\T3AM\Request\RequestHandler;
use In2code\T3AM\Server\Controller\EncryptionKeyController;
use In2code\T3AM\Server\Controller\PingController;
use In2code\T3AM\Server\Controller\UserController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Server
{
    protected const ROUTES = [
        'check/ping' => [PingController::class, 'ping'],
        'user/state' => [UserController::class, 'getUserState'],
        'user/auth' => [UserController::class, 'authUser'],
        'user/get' => [UserController::class, 'getUser'],
        'user/image' => [UserController::class, 'getUserImage'],
        'encryption/getKey' => [EncryptionKeyController::class, 'createEncryptionKey'],
    ];

    /**
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $defaultRequestHandler = GeneralUtility::makeInstance(RequestDispatcher::class);
        $middlewareStack = [
            GeneralUtility::makeInstance(Firewall::class),
            GeneralUtility::makeInstance(Router::class, self::ROUTES),
        ];
        $requestHandler = RequestHandler::fromMiddlewareStack($defaultRequestHandler, $middlewareStack);

        return $requestHandler->handle($request);
    }
}
