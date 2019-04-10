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
use function array_keys;
use function base64_decode;
use function count;
use function explode;
use function file_put_contents;
use function is_array;
use function rtrim;

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
     * BackendUserRepository constructor.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->client = GeneralUtility::makeInstance(Client::class);
        $this->config = GeneralUtility::makeInstance(Config::class);
    }

    /**
     * @param array $foreignUserRow
     *
     * @return array
     */
    public function processUserRow(array $foreignUserRow): array
    {
        if (!isset($foreignUserRow['username'])) {
            return false;
        }

        $foreignUserRow = $this->filterForeignUserRowByLocalFields($foreignUserRow);

        $localUserRow = $this->fetchBeUser($foreignUserRow['username']);

        if (empty($localUserRow)) {
            $this->createUser($foreignUserRow);
        } elseif ($localUserRow['tstamp'] !== $foreignUserRow['tstamp']) {
            $this->updateUser($foreignUserRow);
        } else {
            return $localUserRow;
        }

        if ($this->config->synchronizeImages()) {
            $this->synchronizeImage($localUserRow);
        }

        return $this->fetchBeUser($foreignUserRow['username']);
    }

    /**
     * @param string $username
     *
     * @return bool
     */
    public function removeUser($username)
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable('be_user');
        return $queryBuilder
            ->delete('be_users')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username)))
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
     *
     * @return bool
     */
    protected function deletePreviousAvatars(array $user): bool
    {
        $processedFileRepo = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $resourceFactory = ResourceFactory::getInstance();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connection->getQueryBuilderForTable('sys_file_reference');
        $rows = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', (int)$user['uid']),
                "`tablenames` = 'be_users'",
                "`fieldname` = 'avatar'"
            )
            ->execute()
            ->fetchAll();

        foreach ($rows as $row) {
            $queryBuilder = $this->connection->getQueryBuilderForTable('sys_file_reference');
            $references = $queryBuilder
                ->select('*')
                ->from('sys_file_reference')
                ->where($queryBuilder->expr()->eq('uid_local', (int)$row['uid']))
                ->execute()
                ->fetchAll();

            if (count($references) === 1) {
                try {
                    $file = $resourceFactory->getFileObject($row['uid_local']);
                    $file->getStorage()->setEvaluatePermissions(false);
                    $processedFiles = $processedFileRepo->findAllByOriginalFile($file);
                    foreach ($processedFiles as $processedFile) {
                        $processedFile->delete(true);
                    }
                    $file->delete();
                } catch (FileDoesNotExistException $e) {
                }
            }
        }

        $queryBuilder = $this->connection->getQueryBuilderForTable('sys_file_reference');
        return (bool)$queryBuilder
            ->delete('sys_file_reference')
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
                         ->values(
                             [
                                 'tstamp' => time(),
                                 'crdate' => time(),
                                 'uid_local' => $file->getUid(),
                                 'uid_foreign' => $user['uid'],
                                 'tablenames' => 'be_users',
                                 'fieldname' => 'avatar',
                                 'table_local' => 'sys_file',
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
     *
     * @return bool
     */
    protected function createUser($user): bool
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable('be_user');
        $queryBuilder->getRestrictions()->removeAll();
        $count = (int)$queryBuilder
            ->count('uid')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($user['username'])))
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        if (0 === $count) {
            $this->connection
                ->getQueryBuilderForTable('be_user')
                ->insert('be_users')
                ->values($user)
                ->execute();

            return true;
        }

        return false;
    }

    /**
     * overwrite the local user settings, with settings of central t3am server
     *
     * @param array $user
     *
     * @return bool true, if settings of the be user where updated
     */
    protected function updateUser(array $user): bool
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        $lastChangeTimestamp = $queryBuilder
            ->select('tstamp')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($user['username'])))
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        $queryBuilder = $this->connection->getQueryBuilderForTable('be_users');
        $queryBuilder->update('be_users');
        foreach ($user as $name => $value) {
            $queryBuilder->set($name, $value);
        }
        $queryBuilder
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($user['username'])))
            ->execute();

        return $lastChangeTimestamp !== $user['tstamp'];
    }

    /**
     * @param string $username
     *
     * @return array
     */
    protected function fetchBeUser(string $username): array
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('*')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username)))
            ->setMaxResults(1)
            ->execute()
            ->fetch();
    }

    /**
     * @param array $info
     *
     * @return array
     */
    protected function filterForeignUserRowByLocalFields(array $info): array
    {
        $fields = array_keys(DatabaseUtility::getColumnsFromTable('be_users'));

        $newUser = [];
        foreach ($fields as $field) {
            if (isset($info[$field])) {
                $newUser[$field] = $info[$field];
            }
        }
        return $newUser;
    }
}
