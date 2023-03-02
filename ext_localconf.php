<?php

declare(strict_types=1);

use In2code\T3AM\Client\Authenticator;
use In2code\T3AM\Server\Hooks\ClientTokenGenerator;
use In2code\T3AM\Server\Server;
use In2code\T3AM\Updates\ClientMigrationWizard;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

(function () {
    $extConf = GeneralUtility::makeInstance(
        ExtensionConfiguration::class
    );
    if ($extConf->get('t3am', 'isServer')) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['t3am_server'] = Server::class . '::handle';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_t3am'] = ClientTokenGenerator::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['tx_t3am_client_migration'] = ClientMigrationWizard::class;
    } else {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:backend/Resources/Private/Language/locallang.xlf'][] = 'EXT:t3am/Resources/Private/Language/locallang.xlf';
        if (!$extConf->get('t3am', 'isServer')) {
            ExtensionManagementUtility::addService(
                't3am',
                'auth',
                Authenticator::class,
                [
                    'title' => 'T3AM Client Authenticator',
                    'description' => 'Global authentication service',
                    'subtype' => 'getUserBE,authUserBE',
                    'available' => true,
                    'priority' => 80,
                    'quality' => 80,
                    'os' => '',
                    'exec' => '',
                    'className' => Authenticator::class,
                ]
            );
        }
    }
})();
