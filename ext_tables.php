<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

(function () {
    if ('BE' === TYPO3_MODE) {
        $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        );
        if (!$extConf->get('t3am', 'isServer')) {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
                'dwo_connections',
                'auth',
                \In2code\T3AM\Client\Authenticator::class,
                [
                    'title' => 'T3AM Client Authenticator',
                    'description' => 'Global authentication service',
                    'subtype' => 'getUserBE,authUserBE',
                    'available' => true,
                    'priority' => 80,
                    'quality' => 80,
                    'os' => '',
                    'exec' => '',
                    'className' => \In2code\T3AM\Client\Authenticator::class,
                ]
            );
        }
    }
})();
