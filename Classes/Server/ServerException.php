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
use ReflectionException;
use function sprintf;

/**
 * Class ServerException
 */
class ServerException extends Exception
{
    /**
     * @param string $parameter
     * @return ServerException
     */
    public static function forMissingParameter(string $parameter): ServerException
    {
        return new self(sprintf('Missing parameter $%s', $parameter), 1496395204);
    }

    /**
     * @param ReflectionException $exception
     * @return ServerException
     */
    public static function forInvalidRouteTarget(ReflectionException $exception): ServerException
    {
        return new self('Can not examine route target', 1520607184, $exception);
    }

    /**
     * @param Exception $exception
     * @return ServerException
     */
    public static function forDispatchException(Exception $exception): ServerException
    {
        return new self(sprintf('Exception: %s', $exception->getMessage()), 1496395387, $exception);
    }

    /**
     * @return ServerException
     */
    public static function forInvalidToken(): ServerException
    {
        return new self('Access error', 1519999361);
    }

    /**
     * @return ServerException
     */
    public static function forInvalidRoute(): ServerException
    {
        return new self('Routing error', 1496395045);
    }
}
