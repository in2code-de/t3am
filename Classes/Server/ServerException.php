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

class ServerException extends Exception
{
    public static function forMissingParameter(string $parameter): ServerException
    {
        return new self(sprintf('Missing parameter $%s', $parameter), 1496395204);
    }

    public static function forInvalidRouteTarget(ReflectionException $exception): ServerException
    {
        return new self('Can not examine route target', 1520607184, $exception);
    }
}
