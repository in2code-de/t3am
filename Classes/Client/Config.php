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

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function gettype;
use function is_array;
use function parse_url;
use function rtrim;
use function settype;

/**
 * Class Config
 */
class Config implements SingletonInterface
{
    /**
     * Default configuration values
     */
    protected array $values = [
        'server' => '',
        'token' => '',
        'avatarFolder' => '',
        'selfSigned' => false,
    ];

    /**
     * Config constructor.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        try {
            $config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('t3am');
        } catch (
            ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException
        ) {
            $config = [];
        }

        if (isset($config) && is_array($config)) {
            foreach ($this->values as $option => $default) {
                if (isset($config[$option])) {
                    $value = $config[$option];
                    settype($value, gettype($default));
                    $this->values[$option] = $value;
                }
            }
        }
    }

    public function isValid(): bool
    {
        if (!empty(($this->values['server']))) {
            $parts = parse_url((string) $this->values['server']);
            return !empty($parts['scheme'])
                && !empty($parts['host'])
                && !empty($this->values['token'])
                && $this->ping();
        } else {
            return false;
        }
    }

    public function getServer(): string
    {
        return rtrim(!empty($this->values['server']) ? $this->values['server'] : '', '/') . '/';
    }

    public function getToken(): string
    {
        if (!empty($this->values['token'])) {
            return $this->values['token'];
        } else {
            return '';
        }
    }

    public function synchronizeImages(): bool
    {
        return !empty($this->values['avatarFolder']);
    }

    public function getAvatarFolder(): string
    {
        return $this->values['avatarFolder'];
    }

    public function allowSelfSigned(): bool
    {
        if (!empty($this->values['selfSigned'])) {
            return $this->values['selfSigned'];
        } else {
            return false;
        }
    }

    protected function ping(): bool
    {
        try {
            return GeneralUtility::makeInstance(Client::class)->ping();
        } catch (ClientException) {
            return false;
        }
    }
}
