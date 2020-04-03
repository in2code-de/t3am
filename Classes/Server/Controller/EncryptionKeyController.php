<?php

declare(strict_types=1);
namespace In2code\T3AM\Server\Controller;

use In2code\T3AM\Domain\Factory\DecryptionKeyFactory;
use In2code\T3AM\Domain\Model\EncryptionKey;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EncryptionKeyController
{
    public function createEncryptionKey(): EncryptionKey
    {
        $decryptionKeyFactory = GeneralUtility::makeInstance(DecryptionKeyFactory::class);
        return $decryptionKeyFactory->createPersisted()->deriveEncryptionKey();
    }
}
