<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Factory;

use In2code\T3AM\Domain\Model\EncryptionKey;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EncryptionKeyFactory
{
    public function create(array $row): EncryptionKey
    {
        return GeneralUtility::makeInstance(EncryptionKey::class, $row['uid'], $row['key_value']);
    }
}
