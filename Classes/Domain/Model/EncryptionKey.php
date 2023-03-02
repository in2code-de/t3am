<?php

declare(strict_types=1);
namespace In2code\T3AM\Domain\Model;

use JsonSerializable;

use function base64_encode;
use function openssl_public_encrypt;

class EncryptionKey implements JsonSerializable
{
    /** @var int */
    protected int $uid;

    /** @var string */
    protected string $publicKey;

    public function __construct(int $uid, string $publicKey)
    {
        $this->uid = $uid;
        $this->publicKey = $publicKey;
    }

    public function encrypt(string $input): ?string
    {
        $output = '';
        if (!@openssl_public_encrypt($input, $output, $this->publicKey)) {
            return null;
        }
        return $output;
    }

    public function jsonSerialize(): array
    {
        return [
            'pubKey' => base64_encode($this->publicKey),
            'encryptionId' => $this->uid,
        ];
    }
}
