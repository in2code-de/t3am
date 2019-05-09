<?php
declare(strict_types=1);
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
     *
     * @var array
     */
    protected $values = [
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
        } catch (ExtensionConfigurationExtensionNotConfiguredException $e) {
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
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

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        $parts = parse_url($this->values['server']);
        return !empty($parts['scheme']) && !empty($parts['host']) && !empty($this->values['token']) && $this->ping();
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return rtrim($this->values['server'], '/') . '/';
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->values['token'];
    }

    /**
     * @return bool
     */
    public function synchronizeImages(): bool
    {
        return !empty($this->values['avatarFolder']);
    }

    /**
     * @return string
     */
    public function getAvatarFolder(): string
    {
        return $this->values['avatarFolder'];
    }

    /**
     * @return bool
     */
    public function allowSelfSigned(): bool
    {
        return $this->values['selfSigned'];
    }

    /**
     * @return bool
     */
    protected function ping(): bool
    {
        try {
            return GeneralUtility::makeInstance(Client::class)->ping();
        } catch (ClientException $e) {
            return false;
        }
    }
}
