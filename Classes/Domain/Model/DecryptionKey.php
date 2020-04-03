<?php

declare(strict_types=1);
namespace In2code\T3AM\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use function openssl_pkey_get_details;
use function openssl_private_decrypt;

class DecryptionKey
{
    /** @var int */
    protected $uid;

    /** @var string */
    protected $privateKey;

    public function __construct(int $uid, string $privateKey)
    {
        $this->uid = $uid;
        $this->privateKey = $privateKey;
    }

    public function deriveEncryptionKey(): EncryptionKey
    {
        $privateKey = openssl_pkey_get_private($this->privateKey);
        $publicKey = openssl_pkey_get_details($privateKey)['key'];
        return GeneralUtility::makeInstance(EncryptionKey::class, $this->uid, $publicKey);
    }

    public function decrypt(string $input): ?string
    {
        $output = '';
        if (!@openssl_private_decrypt($input, $output, $this->privateKey)) {
            return null;
        }
        return $output;
    }
}

