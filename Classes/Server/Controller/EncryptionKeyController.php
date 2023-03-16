<?php

declare(strict_types=1);

namespace In2code\T3AM\Server\Controller;

use Doctrine\DBAL\Driver\Exception;
use In2code\T3AM\Domain\Factory\DecryptionKeyFactory;
use In2code\T3AM\Domain\Model\EncryptionKey;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EncryptionKeyController
{
    /**
     * @throws Exception
     */
    public function createEncryptionKey(): EncryptionKey
    {
        $decryptionKeyFactory = GeneralUtility::makeInstance(DecryptionKeyFactory::class);
        return $decryptionKeyFactory->createPersisted()->deriveEncryptionKey();
    }
}
