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

use In2code\T3AM\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use function base64_decode;
use function base64_encode;
use function is_array;
use function is_string;
use function openssl_pkey_export;
use function openssl_pkey_get_details;
use function openssl_pkey_new;
use function openssl_private_decrypt;
use function urldecode;
use function version_compare;

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

    public function createEncryptionKey(): array
    {
        $config = [
            'digest_alg' => 'sha512',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $pubKey = openssl_pkey_get_details($res);

        $this->connectionPool
            ->getQueryBuilderForTable('tx_t3amserver_keys')
            ->insert('tx_t3amserver_keys')
            ->values(['key_value' => base64_encode($privateKey)])
            ->execute();

        return [
            'pubKey' => base64_encode($pubKey['key']),
            'encryptionId' => $this->connectionPool
                ->getConnectionForTable('tx_t3amserver_keys')
                ->lastInsertId(),
        ];
    }

    public function authUser(string $user, string $password, int $encryptionId): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_t3amserver_keys');
        $keyRow = $queryBuilder
            ->select('*')
            ->from('tx_t3amserver_keys')
            ->where($queryBuilder->expr()->eq('uid', $encryptionId))
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if (empty($keyRow) || !is_array($keyRow)) {
            return false;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_t3amserver_keys');
        $queryBuilder
            ->delete('tx_t3amserver_keys')
            ->where($queryBuilder->expr()->eq('uid', $encryptionId))
            ->execute();

        $privateKey = base64_decode($keyRow['key_value']);
        $password = base64_decode(urldecode($password));

        if (!@openssl_private_decrypt($password, $decryptedPassword, $privateKey)) {
            return false;
        }

        $users = GeneralUtility::makeInstance(UserRepository::class)->findUsersByUsername($user);
        $userObject = $users->getActive()->getFirst();

        if (null === $userObject) {
            return false;
        }
        if (version_compare(TYPO3_branch, '9.5', '>=')) {
            return GeneralUtility::makeInstance(PasswordHashFactory::class)
                                 ->get($userObject->getPassword(), 'BE')
                                 ->checkPassword($decryptedPassword, $userObject->getPassword());
        }
        $saltingInstance = SaltFactory::getSaltingInstance($userObject->getPassword());
        return $saltingInstance->checkPassword($decryptedPassword, $userObject->getPassword());
    }
}
