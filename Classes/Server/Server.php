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

use In2code\T3AM\Request\Middleware\Firewall;
use In2code\T3AM\Request\Middleware\Router;
use In2code\T3AM\Request\RequestDispatcher;
use In2code\T3AM\Request\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Server
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestHandler = RequestHandler::fromMiddlewareStack(
            new RequestDispatcher(),
            [
                new Firewall(),
                new Router(),
            ]
        );

        return $requestHandler->handle($request);
    }
}
