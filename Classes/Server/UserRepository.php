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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function base64_encode;

/**
 * Class UserRepository
 */
class UserRepository
{
    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @var array
     */
    protected $fields = [
        'tstamp',
        'username',
        'description',
        'avatar',
        'password',
        'admin',
        'disable',
        'starttime',
        'endtime',
        'lang',
        'email',
        'crdate',
        'realName',
        'disableIPlock',
        'deleted',
    ];

    /**
     * BackendUserRepository constructor.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @param string $user
     *
     * @return string
     */
    public function getUserState(string $user): string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $countActive = $queryBuilder
            ->count('*')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($user)))
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        if ($countActive) {
            return 'okay';
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        $count = $queryBuilder
            ->count('*')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($user)))
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        if ($count > 0) {
            return 'deleted';
        }

        return 'unknown';
    }

    /**
     * @param string $user
     *
     * @return array
     */
    public function getUser(string $user): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');

        return $queryBuilder
            ->select(...$this->fields)
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($user)))
            ->execute()
            ->fetch();
    }

    /**
     * @param string $user
     *
     * @return array
     */
    public function getUserImage(string $user): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');

        $onFileToReference = $queryBuilder
            ->expr()
            ->eq('sys_file_reference.uid_local', $queryBuilder->quoteIdentifier('sys_file.uid'));

        $onFileReferenceToBeUser = $queryBuilder
            ->expr()
            ->eq('be_users.uid', $queryBuilder->quoteIdentifier('sys_file_reference.uid_foreign'));

        $usernameConstraint = $queryBuilder
            ->expr()
            ->eq('be_users.username', $queryBuilder->createNamedParameter($user));

        $tableNamesConstraint = $queryBuilder
            ->expr()
            ->eq('sys_file_reference.tablenames', $queryBuilder->createNamedParameter('be_users'));

        $file = $queryBuilder
            ->select('sys_file.*')
            ->from('sys_file')
            ->rightJoin('sys_file', 'sys_file_reference', 'sys_file_reference', $onFileToReference)
            ->rightJoin('sys_file', 'be_users', 'be_users', $onFileReferenceToBeUser)
            ->where($usernameConstraint)
            ->andWhere($tableNamesConstraint)
            ->execute()
            ->fetch();

        if (!empty($file['uid'])) {
            try {
                $resource = ResourceFactory::getInstance()->getFileObject($file['uid'], $file);

                if ($resource instanceof File && $resource->exists()) {
                    return [
                        'identifier' => $resource->getName(),
                        'b64content' => base64_encode($resource->getContents()),
                    ];
                }
            } catch (FileDoesNotExistException $e) {
            }
        }

        return [];
    }
}
