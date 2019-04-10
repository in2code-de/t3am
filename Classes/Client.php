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

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * Authenticator constructor.
     */
    public function __construct()
    {
        $this->config = GeneralUtility::makeInstance(Config::class);
    }

    /**
     * @return mixed
     *
     * @throws ClientException
     */
    public function getEncryptionKey()
    {
        return $this->request('encryption/getKey');
    }

    /**
     * @param string $user
     *
     * @return mixed
     *
     * @throws ClientException
     */
    public function getUserState($user)
    {
        return $this->request('user/state', ['user' => $user]);
    }

    /**
     * @param string $user
     *
     * @return mixed
     *
     * @throws ClientException
     */
    public function getUserInfo($user)
    {
        return $this->request('user/get', ['user' => $user]);
    }

    /**
     * @param string $user
     *
     * @return mixed
     *
     * @throws ClientException
     */
    public function getUserImage($user)
    {
        return $this->request('user/image', ['user' => $user]);
    }

    /**
     * @param string $user
     * @param string $password
     * @param string $encryptionId
     *
     * @return mixed
     *
     * @throws ClientException
     */
    public function authUser($user, $password, $encryptionId)
    {
        return $this->request('user/auth', ['user' => $user, 'password' => $password, 'encryptionId' => $encryptionId]);
    }

    /**
     * @return bool
     *
     * @throws ClientException
     */
    public function ping()
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
    protected function request($route, array $arguments = [])
    {
        $query = http_build_query(array_merge(['route' => $route, 'token' => $this->config->getToken()], $arguments));

        $sslVerifyHost = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host'];
        $sslVerifyPeer = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer'];
        if ($this->config->allowSelfSigned()) {
            $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host'] = false;
            $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer'] = false;
        }
        $response = $this->getUrl($this->config->getServer() . '?eID=t3am_server&' . $query);
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host'] = $sslVerifyHost;
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer'] = $sslVerifyPeer;

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
     * Improved (and much shorter) version of GeneralUtility::getUrl which always
     * uses cURL to allow self signed certificates without proxy. Does not follow HTTP status 3xx!
     *
     * @param string $url
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getUrl($url)
    {
        $session = curl_init();

        if (!is_resource($session)) {
            return false;
        }

        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_HEADER, 0);
        curl_setopt($session, CURLOPT_NOBODY, 0);
        curl_setopt($session, CURLOPT_HTTPGET, 'GET');
        curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($session, CURLOPT_FAILONERROR, 1);
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, max(0, (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlTimeout']));

        $applicant = function ($options) use ($session) {
            foreach ($options as $key => $option) {
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS'][$key]) {
                    curl_setopt($session, $option[0], $option[1]);
                }
            }
        };

        if ($this->config->allowSelfSigned() || !$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer']) {
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            if ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer']) {
                $options = [
                    'ssl_cafile' => [CURLOPT_CAINFO, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_cafile']],
                    'ssl_capath' => [CURLOPT_CAPATH, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_capath']],
                ];
                array_map($applicant, $options);
            }
        }
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']) {
            curl_setopt($session, CURLOPT_PROXY, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);
            curl_setopt($session, CURLOPT_SSL_VERIFYHOST, (bool)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host']);
            $options = [
                'curlProxyNTLM' => [CURLOPT_PROXYAUTH, CURLAUTH_NTLM],
                'curlProxyTunnel' => [CURLOPT_HTTPPROXYTUNNEL, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']],
                'curlProxyUserPass' => [CURLOPT_PROXYUSERPWD, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']],
            ];
            array_map($applicant, $options);
        }
        $content = curl_exec($session);

        curl_close($session);

        return $content;
    }
}
