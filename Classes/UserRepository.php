<?php
namespace In2code\T3AM\Client;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class UserRepository
 */
class UserRepository
{
    /**
     * @var DatabaseConnection
     */
    protected $connection = null;

    /**
     * BackendUserRepository constructor.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        $this->connection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param array $info
     * @return bool
     */
    public function processInfo(array $info)
    {
        if (!isset($info['username'])) {
            return false;
        }

        $fields = array_keys($this->connection->admin_get_fields('be_users'));

        $user = [];
        foreach ($fields as $field) {
            if (isset($info[$field])) {
                $user[$field] = $info[$field];
            }
        }

        $where = 'username = ' . $this->connection->fullQuoteStr($info['username'], 'be_users');

        if (0 === $this->connection->exec_SELECTcountRows('*', 'be_users', $where)) {
            $this->connection->exec_INSERTquery('be_users', $user);
        } else {
            $this->connection->exec_UPDATEquery('be_users', $where, $user);
        }

        return $this->connection->exec_SELECTgetSingleRow('*', 'be_users', $where);
    }

    /**
     * @param string $username
     * @return bool
     */
    public function removeUser($username)
    {
        $where = 'username = ' . $this->connection->fullQuoteStr($username, 'be_users');
        return (bool)$this->connection->exec_DELETEquery('be_users', $where);
    }
}
