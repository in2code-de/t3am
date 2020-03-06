<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Factory;

use In2code\T3AM\Domain\Model\EncryptionKey;
use In2code\T3AM\Domain\Repository\EncryptionKeyRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function openssl_pkey_export;
use function openssl_pkey_get_details;
use function openssl_pkey_new;
use const OPENSSL_KEYTYPE_RSA;

class EncryptionKeyFactory
{
    public function fromRow(array $row): EncryptionKey
    {
        return GeneralUtility::makeInstance(EncryptionKey::class, $row['uid'], $row['private_key'], $row['public_key']);
    }

    public function create(): ?EncryptionKey
    {
        $config = [
            'digest_alg' => 'sha512',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKey = '';
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        $encryptionKeyRepo = GeneralUtility::makeInstance(EncryptionKeyRepository::class);
        return $encryptionKeyRepo->persist($privateKey, $publicKey);
    }
}
