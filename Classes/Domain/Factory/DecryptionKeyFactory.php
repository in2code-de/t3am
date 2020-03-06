<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Factory;

use In2code\T3AM\Domain\Model\DecryptionKey;
use In2code\T3AM\Domain\Repository\DecryptionKeyRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function openssl_pkey_export;
use function openssl_pkey_get_details;
use function openssl_pkey_new;
use const OPENSSL_KEYTYPE_RSA;

class DecryptionKeyFactory
{
    public function fromRow(array $row): DecryptionKey
    {
        return GeneralUtility::makeInstance(DecryptionKey::class, $row['uid'], $row['private_key'], $row['public_key']);
    }

    public function createPersisted(): ?DecryptionKey
    {
        $config = [
            'digest_alg' => 'sha512',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKey = '';
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);

        $decryptionKeyRepo = GeneralUtility::makeInstance(DecryptionKeyRepository::class);
        return $decryptionKeyRepo->persist($privateKey);
    }
}
