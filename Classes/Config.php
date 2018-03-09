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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        'selfSigned' => false,
    ];

    /**
     * Config constructor.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['t3am'])) {
            $config = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['t3am']);
            if (is_array($config)) {
                foreach ($this->values as $option => $default) {
                    if (isset($config[$option])) {
                        $value = $config[$option];
                        settype($value, gettype($default));
                        $this->values[$option] = $value;
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $parts = parse_url($this->values['server']);
        return !empty($parts['scheme']) && !empty($parts['host']) && !empty($this->values['token']) && $this->ping();
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return rtrim($this->values['server'], '/') . '/';
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->values['token'];
    }

    /**
     * @return string
     */
    public function allowSelfSigned()
    {
        return $this->values['selfSigned'];
    }

    /**
     * @return bool
     */
    protected function ping()
    {
        try {
            return GeneralUtility::makeInstance(Client::class)->ping();
        } catch (ClientException $e) {
            return false;
        }
    }
}
