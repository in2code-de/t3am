<?php

defined('TYPO3_MODE') or die();

(static function () {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:backend/Resources/Private/Language/locallang.xlf'][] = 'EXT:t3am/Resources/Private/Language/locallang.xlf';

    $config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\T3AM\Client\Config::class);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
        'dwo_connections',
        'auth',
        \In2code\T3AM\Client\Authenticator::class,
        [
            'title' => 'T3AM Client Authenticator',
            'description' => 'Global authentication service',
            'subtype' => 'getUserBE,authUserBE',
            'available' => (int)$config->isValid(),
            'priority' => 80,
            'quality' => 80,
            'os' => '',
            'exec' => '',
            'className' => \In2code\T3AM\Client\Authenticator::class,
        ]
    );
})();
