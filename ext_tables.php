<?php
call_user_func(
    function () {
        if ('BE' === TYPO3_MODE) {
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
        }
    }
);
