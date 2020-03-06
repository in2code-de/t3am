<?php
$GLOBALS['TCA']['tx_t3am_client']['ctrl']['hideTable'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('t3am', 'isServer') ? 0 : 1;
