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

use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rsaauth\RsaEncryptionDecoder;
use function base64_decode;
use function base64_encode;
use function class_exists;
use function is_string;
use function openssl_public_encrypt;
use function strlen;
use function urlencode;

/**
 * Class Authenticator
 */
class Authenticator extends AbstractAuthenticationService implements SingletonInterface
{
    /**
     * @var Client
     */
    protected $client = null;

    /**
     * @var UserRepository
     */
    protected $userRepository = null;

    /**
     * @var bool
     */
    protected $shouldAuth = false;

    /**
     * Authenticator constructor.
     */
    public function __construct()
    {
        $this->client = GeneralUtility::makeInstance(Client::class);
        $this->userRepository = GeneralUtility::makeInstance(UserRepository::class);
    }

    /**
     * @return array|bool
     */
    public function getUser()
    {
        $username = $this->login['uname'];
        if (!is_string($username) || strlen($username) <= 2) {
            return false;
        }

        try {
            $state = $this->client->getUserState($username);
        } catch (ClientException $e) {
            return false;
        }

        if ('okay' === $state) {
            try {
                $userRow = $this->client->getUserRow($username);
            } catch (ClientException $e) {
                return false;
            }
            $this->shouldAuth = true;
            return $this->userRepository->processUserRow($userRow);
        } elseif ('deleted' === $state) {
            $this->userRepository->removeUser($username);
        }

        return false;
    }

    /**
     * @param array $user
     *
     * @return int
     */
    public function authUser(array $user)
    {
        if (!$this->shouldAuth) {
            return 100;
        }
        if (!isset($this->login['uident_text'])) {
            if (class_exists(RsaEncryptionDecoder::class)) {
                $rsaEncryptionDecoder = GeneralUtility::makeInstance(RsaEncryptionDecoder::class);
                $clearTextPassword = $rsaEncryptionDecoder->decrypt($this->login['uident']);
            } else {
                $clearTextPassword = $this->login['uident'];
            }
            $this->login['uident_text'] = $clearTextPassword;
        }

        try {
            $pubKeyArray = $this->client->getEncryptionKey();
        } catch (ClientException $e) {
            return 100;
        }

        // prevent error output which would show the plain text password
        $publicKey = base64_decode($pubKeyArray['pubKey']);
        if (true === @openssl_public_encrypt(
                $this->login['uident_text'],
                $encrypted,
                $publicKey,
                OPENSSL_PKCS1_OAEP_PADDING)
        ) {
            $encodedPassword = urlencode(base64_encode($encrypted));

            try {
                if ($this->client->authUser($user['username'], $encodedPassword, (int)$pubKeyArray['encryptionId'])) {
                    return 200;
                } else {
                    return 0;
                }
            } catch (ClientException $e) {
                return 100;
            }
        }

        return 100;
    }
}
