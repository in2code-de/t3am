<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

try {
    $GLOBALS['TCA']['tx_t3am_client']['ctrl']['hideTable'] = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('t3am', 'isServer') ? 0 : 1;
} catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
}
