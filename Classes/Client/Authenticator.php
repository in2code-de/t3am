<?php

declare(strict_types=1);
namespace In2code\T3AM\Client;

/*
 * (c) 2018 in2code GmbH https://www.in2code.de
 * Oliver Eglseder <php@vxvr.de>
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

use Doctrine\DBAL\Driver\Exception;
use In2code\T3AM\Domain\Collection\UserCollection;
use In2code\T3AM\Domain\Factory\EncryptionKeyFactory;
use In2code\T3AM\Domain\Factory\UserFactory;
use In2code\T3AM\Domain\Repository\UserRepository as NewUserRepository;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function base64_decode;
use function base64_encode;
use function is_string;
use function strlen;
use function urlencode;

/**
 * Class Authenticator
 */
class Authenticator extends AbstractAuthenticationService implements SingletonInterface
{
    protected ?Client $client = null;

    protected ?UserRepository $userRepository = null;

    protected bool $shouldAuth = false;

    /**
     * Authenticator constructor.
     */
    public function __construct()
    {
        $this->client = GeneralUtility::makeInstance(Client::class);
        $this->userRepository = GeneralUtility::makeInstance(UserRepository::class);
    }

    /**
     * @throws Exception
     */
    public function getUser(): bool|array
    {
        $config = GeneralUtility::makeInstance(Config::class);
        if (!$config->isValid()) {
            return false;
        }

        $username = !empty($this->login['uname']) ? $this->login['uname'] : null;
        if (!is_string($username) || strlen($username) <= 2) {
            return false;
        }

        try {
            $state = $this->client->getUserState($username);
        } catch (ClientException) {
            return false;
        }

        if (UserCollection::USER_OKAY === $state) {
            try {
                $userRow = $this->client->getUserRow($username);
            } catch (ClientException) {
                return false;
            }
            $this->shouldAuth = true;

            $userFactory = GeneralUtility::makeInstance(UserFactory::class);
            $userRow['uid'] = 0;
            $user = $userFactory->fromRow($userRow);
            $userRepository = GeneralUtility::makeInstance(NewUserRepository::class);
            $userRepository->updateLocalUserWithNewUser($user);
            $row = $userRepository->getFirstActiveUserRaw($username);

            if (GeneralUtility::makeInstance(Config::class)->synchronizeImages()) {
                $this->userRepository->synchronizeImage($row);
            }

            return $row;
        } elseif (UserCollection::USER_DELETED === $state) {
            $userRepository = GeneralUtility::makeInstance(NewUserRepository::class);
            $userRepository->deleteByUsername($username);
        }

        return false;
    }

    public function authUser(array $user): int
    {
        if (!$this->shouldAuth) {
            return 100;
        }
        $password = $this->getPassword();

        if ($password === null) {
            return 100;
        }

        try {
            $pubKeyArray = $this->client->getEncryptionKey();
        } catch (ClientException) {
            return 100;
        }

        if (empty($pubKeyArray['encryptionId']) || empty($pubKeyArray['pubKey'])) {
            return 100;
        }

        $row = [
            'uid' => $pubKeyArray['encryptionId'],
            'public_key' => base64_decode((string) $pubKeyArray['pubKey']),
        ];
        $encryptionKeyFactory = GeneralUtility::makeInstance(EncryptionKeyFactory::class);
        $encryptionKey = $encryptionKeyFactory->fromRow($row);

        $encrypted = $encryptionKey->encrypt($password);
        if (null === $encrypted) {
            return 100;
        }

        $encodedPassword = urlencode(base64_encode((string) $encrypted));

        try {
            if (!empty($user['username']) && $this->client->authUser($user['username'], $encodedPassword, (int)$pubKeyArray['encryptionId'])) {
                return 200;
            } else {
                return 0;
            }
        } catch (ClientException) {
            return 100;
        }
    }

    protected function getPassword(): ?string
    {
        if (!isset($this->login['uident_text'])) {
            if (!empty($this->login['uident'])) {
                $clearTextPassword = $this->login['uident'];
                $this->login['uident_text'] = $clearTextPassword;
            } else {
                $this->login['uident_text'] = null;
            }
        }
        return $this->login['uident_text'];
    }
}
