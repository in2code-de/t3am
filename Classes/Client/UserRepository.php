<?php

declare(strict_types=1);
namespace In2code\T3AM\Client;

/*
 * (c) 2018 in2code GmbH https://www.in2code.de
 * Oliver Eglseder <php@vxvr.de>
 * Stefan Busemann <stefan.busemann@in2code.de>
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

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Context\Context;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function array_keys;
use function base64_decode;
use function count;
use function explode;
use function file_put_contents;
use function is_array;
use function rtrim;
use function settype;
use function time;

/**
 * Class UserRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserRepository
{
    protected ?ConnectionPool $connection = null;

    protected ?Client $client = null;

    protected ?Config $config = null;

    protected ?LoggerInterface $logger = null;

    protected array $types = [
        'deleted' => 'int',
        'disable' => 'int',
        'admin' => 'int',
    ];

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
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }

    /**
     * @throws Exception
     */
    public function synchronizeImage(array $user): bool
    {
        try {
            $imageData = $this->client->getUserImage($user['username']);
        } catch (ClientException) {
            return false;
        }

        if (empty($imageData)) {
            return false;
        }

        try {
            $this->deletePreviousAvatars($user);
            $this->updateAvatar($user, $imageData);
        } catch (ExistingTargetFolderException|IllegalFileExtensionException|InsufficientFolderWritePermissionsException|InsufficientFolderAccessPermissionsException) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    protected function deletePreviousAvatars(array $user): bool
    {
        $processedFileRepo = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        /* @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connection->getQueryBuilderForTable('sys_file_reference');
        $rows = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', (int)$user['uid']),
                "`tablenames` = 'be_users'",
                "`fieldname` = 'avatar'"
            )
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $queryBuilder = $this->connection->getQueryBuilderForTable('sys_file_reference');
            $references = $queryBuilder
                ->select('*')
                ->from('sys_file_reference')
                ->where($queryBuilder->expr()->eq('uid_local', (int)$row['uid']))
                ->executeQuery()
                ->fetchAllAssociative();

            if (count($references) === 1) {
                try {
                    $file = $resourceFactory->getFileObject($row['uid_local']);
                    $file->getStorage()->setEvaluatePermissions(false);
                    $processedFiles = $processedFileRepo->findAllByOriginalFile($file);
                    foreach ($processedFiles as $processedFile) {
                        $processedFile->delete(true);
                    }
                    $file->delete();
                } catch (FileDoesNotExistException) {
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
            ->executeStatement();
    }

    /**
     *
     * @throws ExistingTargetFolderException
     * @throws InsufficientFolderAccessPermissionsException
     * @throws InsufficientFolderWritePermissionsException
     * @throws IllegalFileExtensionException
     */
    protected function updateAvatar(array $user, array $imageData): void
    {
        $processedFileRep = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $avatarFolder = $this->config->getAvatarFolder();
        [$storageId, $folderId] = explode(':', $avatarFolder);
        $storage = $resourceFactory->getStorageObject($storageId);
        $storage->setEvaluatePermissions(false);

        if (!$storage->hasFolder($folderId)) {
            $folder = $storage->createFolder($folderId);
        } else {
            $folder = $storage->getFolder($folderId);
        }

        $tmpFile = GeneralUtility::tempnam('t3am_avatar');
        file_put_contents($tmpFile, base64_decode((string) $imageData['b64content']));



        if (!$folder->hasFile($imageData['identifier'])) {
            $file = $folder->addFile($tmpFile, $imageData['identifier']);
        } else {
            $file = $resourceFactory->getFileObjectFromCombinedIdentifier(
                rtrim($avatarFolder, '/') . '/' . $imageData['identifier']
            );
            $folder->getStorage()->replaceFile($file, $tmpFile);
        }

        // Always insert the new file reference, because the old one is always deleted
        /* @var QueryBuilder $queryBuilder */
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
            ->executeStatement();

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
     * @throws Exception
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
            ->executeQuery()
            ->fetchOne();

        if (0 === $count) {
            $this->connection
                ->getQueryBuilderForTable('be_user')
                ->insert('be_users')
                ->values($user)
                ->executeStatement();

            return true;
        }

        return false;
    }

    /**
     * overwrite the local user settings, with settings of central t3am server
     *
     * @return bool true, if settings of the be user where updated
     * @throws Exception
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
            ->executeQuery()
            ->fetchOne();

        $queryBuilder = $this->connection->getQueryBuilderForTable('be_users');
        $queryBuilder->update('be_users');
        foreach ($user as $name => $value) {
            $queryBuilder->set($name, $value);
        }
        $queryBuilder
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($user['username'])))
            ->executeStatement();

        return $lastChangeTimestamp !== $user['tstamp'];
    }

    /**
     * @throws Exception
     */
    protected function fetchBeUser(string $username): array
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('*')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if (!is_array($result)) {
            return [];
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    protected function filterForeignUserRowByLocalFields(array $info): array
    {
        $fields = array_keys(DatabaseUtility::getColumnsFromTable('be_users'));

        $newUser = [];
        foreach ($fields as $field) {
            if (isset($info[$field])) {
                $value = $info[$field];
                if (isset($this->types[$field])) {
                    settype($value, $this->types[$field]);
                }

                $newUser[$field] = $value;
            }
        }
        return $newUser;
    }

    /**
     * @throws AspectNotFoundException
     */
    protected function shouldUpdate(array $localUserRow, array $foreignUserRow): bool
    {
        return $this->isDeleted($localUserRow)
               || $this->isDisabled($localUserRow)
               || $this->isOutDated($localUserRow, $foreignUserRow);
    }

    /**
     * @param array $user
     * @return bool
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function isDeleted(array $user): bool
    {
        if (isset($GLOBALS['TCA']['be_users']['ctrl']['delete'])) {
            $field = $GLOBALS['TCA']['be_users']['ctrl']['delete'];
            if (array_key_exists($field, $user)) {
                return (bool)$user[$field];
            } else {
                $this->logger->error(
                    'User row is missing the delete field. T3AM assumes the user is deleted.',
                    ['field_name' => $field, 'user_row' => $user]
                );
                return true;
            }
        }
        return false;
    }

    /**
     * @throws AspectNotFoundException
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function isDisabled(array $user): bool
    {
        if (isset($GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['disabled'])) {
            $field = $GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['disabled'];
            if (array_key_exists($field, $user)) {
                if ($user[$field]) {
                    return true;
                }
            } else {
                $this->logger->error(
                    'User row is missing the disable field. T3AM assumes the user is disabled.',
                    ['field_name' => $field, 'user_row' => $user]
                );
                return true;
            }
        }
        if (isset($GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['starttime'])) {
            $field = $GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['starttime'];
            if (array_key_exists($field, $user)) {
                if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp') < $user[$field]) {
                    return true;
                }
            } else {
                $this->logger->error(
                    'User row is missing the start time field. T3AM assumes the user is disabled.',
                    ['field_name' => $field, 'user_row' => $user]
                );
                return true;
            }
        }
        if (isset($GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['endtime'])) {
            $field = $GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['endtime'];
            if (array_key_exists($field, $user)) {
                if (0 !== (int)$user[$field] && GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp') > $user[$field]) {
                    return true;
                }
            } else {
                $this->logger->error(
                    'User row is missing the end time field. T3AM assumes the user is disabled.',
                    ['field_name' => $field, 'user_row' => $user]
                );
                return true;
            }
        }
        return false;
    }

    protected function isOutDated(array $localUserRow, array $foreignUserRow): bool
    {
        return $localUserRow['tstamp'] !== $foreignUserRow['tstamp'];
    }
}
