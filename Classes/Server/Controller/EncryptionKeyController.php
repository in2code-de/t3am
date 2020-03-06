<?php
declare(strict_types=1);
namespace In2code\T3AM\Server\Controller;

use In2code\T3AM\Domain\Factory\EncryptionKeyFactory;
use In2code\T3AM\Domain\Model\EncryptionKey;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EncryptionKeyController
{
    public function createEncryptionKey(): EncryptionKey
    {
        $encryptionKeyFactory = GeneralUtility::makeInstance(EncryptionKeyFactory::class);
        return $encryptionKeyFactory->create();
    }
}
