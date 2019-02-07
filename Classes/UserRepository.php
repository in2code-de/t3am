<?php

namespace In2code\T3AM\Client;

/*
 * Copyright (C) 2018-2019 Oliver Eglseder <php@vxvr.de>, Stefan Busemann <stefan.busemann@in2code.de>,  in2code GmbH
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
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class UserRepository
 */
class UserRepository
{
    /**
     * @var ConnectionPool
     */
    protected $connection = null;

    /**
     * @var Client
     */
    protected $client = null;

    /**
     * @var Config
     */
    protected $config = null;

    /**
     * @var QueryBuilder
     */
    protected $whereForUserName;

    /**
     * @var QueryBuilder
     */
    protected $beUserQueryBuilder;

    /**
     * BackendUserRepository constructor.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->client = GeneralUtility::makeInstance(Client::class);
        $this->config = GeneralUtility::makeInstance(Config::class);
        $this->beUserQueryBuilder = $this->connection->getQueryBuilderForTable('be_users');
    }

    /**
     * @param array $info
     * @return array|bool
     */
    public function processInfo(array $info)
    {
        if (!isset($info['username'])) {
            return false;
        }

        $fields = array_keys(DatabaseUtility::getColumnsFromTable('be_users'));

        $user = [];
        foreach ($fields as $field) {
            if (isset($info[$field])) {
                $user[$field] = $info[$field];
            }
        }

        if ($this->createUser($user)) {
            $userChanged = true;
        } else {
            $userChanged = $this->updateBEUser($user);
        }

        $beUser = $this->getBeUser($user);

        if ($userChanged && $this->config->synchronizeImages()) {
            $this->synchronizeImage($beUser);
        }

        return $beUser;
    }

    /**
     * @param string $username
     * @return bool
     */
    public function removeUser($username)
    {
        return (bool)$this->beUserQueryBuilder
            ->delete('be_users')
            ->from('be_users')
            ->where($this->getWhereForUserName($username))
            ->execute();
    }

    /**
     * @param array $user
     *
     * @return bool
     */
    protected function synchronizeImage(array $user)
    {
        try {
            $imageData = $this->client->getUserImage($user['username']);
        } catch (ClientException $e) {
            return false;
        }

        if (!is_array($imageData)) {
            return false;
        }

        $this->deletePreviousAvatars($user);

        try {
            $this->updateAvatar($user, $imageData);
        } catch (ExistingTargetFolderException $e) {
        } catch (IllegalFileExtensionException $e) {
        } catch (InsufficientFolderWritePermissionsException $e) {
        } catch (InsufficientFolderAccessPermissionsException $e) {
        }

        return true;
    }

    /**
     * @param array $user
     * @return bool
     */
    protected function deletePreviousAvatars(array $user): bool
    {
        $processedFileRep = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $resourceFactory = ResourceFactory::getInstance();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connection->getQueryBuilderForTable('sys_file_reference');
        $rows = $queryBuilder->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', (int)$user['uid']),
                "`tablenames` = 'be_users'",
                "`fieldname` = 'avatar'"
            )
            ->execute()
            ->fetchAll();

        foreach ($rows as $row) {
            $references = $queryBuilder->select('*')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq('uid_local', (int)$row['uid'])
                )
                ->execute()
                ->fetchAll();

            if (count($references) === 1) {
                try {
                    $file = $resourceFactory->getFileObject($row['uid_local']);
                    $file->getStorage()->setEvaluatePermissions(false);
                    $processedFiles = $processedFileRep->findAllByOriginalFile($file);
                    foreach ($processedFiles as $processedFile) {
                        $processedFile->delete(true);
                    }
                    $file->delete();
                } catch (FileDoesNotExistException $e) {
                }
            }
        }

        return (bool)$queryBuilder->delete('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', (int)$user['uid']),
                "`tablenames` = 'be_users'",
                "`fieldname` = 'avatar'"
            )
            ->execute();
    }

    /**
     * @param array $user
     * @param array $imageData
     *
     * @throws ExistingTargetFolderException
     * @throws InsufficientFolderAccessPermissionsException
     * @throws InsufficientFolderWritePermissionsException
     * @throws IllegalFileExtensionException
     */
    protected function updateAvatar(array $user, array $imageData)
    {
        $processedFileRep = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $resourceFactory = ResourceFactory::getInstance();

        $avatarFolder = $this->config->getAvatarFolder();
        list($storageId, $folderId) = explode(':', $avatarFolder);
        $storage = $resourceFactory->getStorageObject($storageId);
        $storage->setEvaluatePermissions(false);

        if (!$storage->hasFolder($folderId)) {
            $folder = $storage->createFolder($folderId);
        } else {
            $folder = $storage->getFolder($folderId);
        }

        $tmpFile = GeneralUtility::tempnam('t3am_avatar');
        file_put_contents($tmpFile, base64_decode($imageData['b64content']));

        if (!$folder->hasFile($imageData['identifier'])) {
            $file = $folder->addFile($tmpFile, $imageData['identifier']);

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $this->connection->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->insert('sys_file_reference')
                ->values([
                        'tstamp' => time(),
                        'crdate' => time(),
                        'uid_local' => $file->getUid(),
                        'uid_foreign' => $user['uid'],
                        'tablenames' => 'be_users',
                        'fieldname' => 'avatar',
                        'table_local' => 'sys_file'
                    ]
                )
                ->execute();

        } else {
            $file = $resourceFactory->getFileObjectFromCombinedIdentifier(
                rtrim($avatarFolder, '/') . '/' . $imageData['identifier']
            );
            $folder->getStorage()->replaceFile($file, $tmpFile);
        }

        $processedFiles = $processedFileRep->findAllByOriginalFile($file);
        foreach ($processedFiles as $processedFile) {
            $processedFile->delete(true);
        }
    }

    /**
     * check if the given users exists at the local instance, if not, create the user
     *
     * @param $user
     * @return bool
     */
    protected function createUser($user): bool
    {
        $count = $this->beUserQueryBuilder
            ->count('uid')
            ->from('be_users')
            ->where($this->getWhereForUserName($user))
            ->execute()
            ->fetchColumn(0);

        if (0 === $count) {
            $this->beUserQueryBuilder
                ->insert('be_users')
                ->values($user)
                ->execute();

            return true;
        }

        return false;
    }

    /**
     * created a querybuilder where statement
     *
     * @param $userName
     * @return String
     */
    protected function getWhereForUserName($userName): String
    {
        $this->beUserQueryBuilder->getRestrictions()->removeAll();

        return $this->beUserQueryBuilder->expr()->eq('username', $this->beUserQueryBuilder->createNamedParameter($userName['username']));
    }

    /**
     * overwrite the local user settings, with settings of central t3am server
     *
     * @param $user
     * @return bool true, if settings of the be user where updated
     */
    protected function updateBEUser($user): bool
    {
        $currentLocalBEUser = $this->beUserQueryBuilder
            ->select('*')
            ->from('be_users')
            ->where($this->getWhereForUserName($user))
            ->execute()
            ->fetch();

        $this->connection->getConnectionForTable('be_users')->update(
            'be_users',
            $user,
            ['username' => $user['username']]
        );

        return $currentLocalBEUser['tstamp'] !== $user['tstamp'];

    }

    /**
     * retuns the beuser as array from the given username
     *
     * @param $user
     * @return array
     */
    protected function getBeUser($user): array
    {
        $this->beUserQueryBuilder = $this->connection->getQueryBuilderForTable('be_users');
        $this->beUserQueryBuilder->getRestrictions()->removeAll();
        return $this->beUserQueryBuilder
            ->select('*')
            ->from('be_users')
            ->where($this->getWhereForUserName($user))
            ->execute()
            ->fetch();
    }
}
