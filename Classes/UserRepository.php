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
     * @var DatabaseConnection
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
        $this->connection = $GLOBALS['TYPO3_DB'];
        $this->client = GeneralUtility::makeInstance(Client::class);
        $this->config = GeneralUtility::makeInstance(Config::class);
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
            $userChanged = true;
        } else {
            $oldUser = $this->connection->exec_SELECTgetSingleRow('tstamp', 'be_users', $where);
            $userChanged = $oldUser['tstamp'] !== $user['tstamp'];
            $this->connection->exec_UPDATEquery('be_users', $where, $user);
        }

        $user = $this->connection->exec_SELECTgetSingleRow('*', 'be_users', $where);

        if ($userChanged && $this->config->synchronizeImages()) {
            $this->synchronizeImage($user);
        }

        return $user;
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
     */
    protected function deletePreviousAvatars(array $user)
    {
        $processedFileRep = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $resourceFactory = ResourceFactory::getInstance();

        $rows = $this->connection->exec_SELECTgetRows(
            '*',
            'sys_file_reference',
            'uid_foreign = ' . (int)$user['uid'] . ' AND tablenames = "be_users" AND fieldname = "avatar"'
        );
        foreach ($rows as $row) {
            $references = $this->connection->exec_SELECTgetRows(
                '*',
                'sys_file_reference',
                'uid_local = ' . (int)$row['uid']
            );
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
        $this->connection->exec_DELETEquery(
            'sys_file_reference',
            'uid_foreign = ' . (int)$user['uid'] . ' AND tablenames = "be_users" AND fieldname = "avatar"'
        );
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
            $this->connection->exec_INSERTquery(
                'sys_file_reference',
                [
                    'tstamp' => time(),
                    'crdate' => time(),
                    'uid_local' => $file->getUid(),
                    'uid_foreign' => $user['uid'],
                    'tablenames' => 'be_users',
                    'fieldname' => 'avatar',
                    'table_local' => 'sys_file',
                ]
            );
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
}
