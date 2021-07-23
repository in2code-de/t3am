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

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_merge;
use function http_build_query;
use function is_string;
use function json_decode;

/**
 * Class Client
 */
class Client
{
    /**
     * @var Config
     */
    protected $config = null;

    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * Authenticator constructor.
     */
    public function __construct()
    {
        $this->config = GeneralUtility::makeInstance(Config::class);
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }

    /**
     * @return array
     *
     * @throws ClientException
     */
    public function getEncryptionKey(): array
    {
        return $this->request('encryption/getKey');
    }

    /**
     * @param string $user
     *
     * @return string
     *
     * @throws ClientException
     */
    public function getUserState(string $user): string
    {
        return $this->request('user/state', ['user' => $user]);
    }

    /**
     * @param string $user
     *
     * @return array
     *
     * @throws ClientException
     */
    public function getUserRow(string $user): array
    {
        return $this->request('user/get', ['user' => $user]);
    }

    /**
     * @param string $user
     *
     * @return array
     *
     * @throws ClientException
     */
    public function getUserImage(string $user): array
    {
        return $this->request('user/image', ['user' => $user]);
    }

    /**
     * @param string $user
     * @param string $password
     * @param int $encryptionId
     *
     * @return bool
     *
     * @throws ClientException
     */
    public function authUser(string $user, string $password, int $encryptionId): bool
    {
        return $this->request('user/auth', ['user' => $user, 'password' => $password, 'encryptionId' => $encryptionId]);
    }

    /**
     * @return bool
     *
     * @throws ClientException
     */
    public function ping(): bool
    {
        return $this->request('check/ping');
    }

    /**
     * @param string $route
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws ClientException
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function request(string $route, array $arguments = [])
    {
        $query = http_build_query(array_merge(['route' => $route, 'token' => $this->config->getToken()], $arguments));

        $response = $this->getUrl($this->config->getServer() . '?eID=t3am_server&' . $query);

        if (!is_string($response)) {
            throw new ClientException('The API endpoint did not return a valid response');
        }
        $apiResult = json_decode($response, true);
        $result = false;
        if (isset($apiResult['error']) && false === $apiResult['error'] && isset($apiResult['data'])) {
            $result = $apiResult['data'];
        }

        return $result;
    }

    /**
     * @param string $url
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getUrl(string $url)
    {
        $verify = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['verify'];
        if (true === $this->config->allowSelfSigned()) {
            $GLOBALS['TYPO3_CONF_VARS']['HTTP']['verify'] = false;
        }

        $report = [];

        $response = GeneralUtility::makeInstance(RequestFactoryInterface::class)->request($url);
        $report['error'] = $response->getStatusCode();
        $content = $response->getBody()->getContents();

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['verify'] = $verify;


        if ($report['error'] !== 200) {
            $this->logger->error('Received error on T3AM client request', $report);
        }

        return $content;
    }
}
