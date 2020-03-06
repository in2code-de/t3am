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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function is_string;

class SecurityService
{
    protected $connectionPool;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function isValid(string $token): bool
    {
        if (!is_string($token)) {
            return false;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_t3amserver_client');

        return (bool)$queryBuilder
            ->count('uid')
            ->from('tx_t3amserver_client')
            ->where($queryBuilder->expr()->eq('token', $queryBuilder->createNamedParameter($token)))
            ->execute()
            ->fetchColumn();
    }
}
