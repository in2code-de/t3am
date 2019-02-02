<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:backend/Resources/Private/Language/locallang.xlf'][] = 'EXT:t3am/Resources/Private/Language/locallang.xlf';
});
