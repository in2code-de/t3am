<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:backend/Resources/Private/Language/locallang.xlf'][] = 'EXT:t3am/Resources/Private/Language/locallang.xlf';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['t3am_server'] = \In2code\T3AM\Server\Server::class . '::handle';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_t3am'] = \In2code\T3AM\Server\Hooks\ClientTokenGenerator::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['tx_t3am_client_migration'] = \In2code\T3AM\Updates\ClientMigrationWizard::class;
    }
);
