<?php

declare(strict_types=1);

namespace In2code\T3AM\Server\Hooks;

use Exception;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function is_string;
use function random_bytes;

class ClientTokenGenerator
{
    /**
     * @throws Exception
     */
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        foreach (array_keys($dataHandler->datamap['tx_t3am_client'] ?? []) as $uid) {
            if (is_string($uid) && str_starts_with($uid, 'NEW')) {
                $token = GeneralUtility::hmac(random_bytes(256), 'tx_t3am_client');
                $dataHandler->datamap['tx_t3am_client'][$uid]['token'] = $token;
            }
        }
    }
}
