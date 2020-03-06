<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Model;

use JsonSerializable;
use function openssl_private_decrypt;

class EncryptionKey implements JsonSerializable
{
    /** @var int */
    protected $uid;

    /** @var string */
    protected $privateKey;

    /** @var string */
    protected $publicKey;

    public function __construct(int $uid, string $privateKey, string $publicKey)
    {
        $this->uid = $uid;
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function decrypt(string $input): ?string
    {
        $output = '';
        if (!@openssl_private_decrypt($input, $output, $this->privateKey)) {
            return null;
        }
        return $output;
    }

    public function jsonSerialize()
    {
        return [
            'pubKey' => base64_encode($this->publicKey),
            'encryptionId' => $this->uid,
        ];
    }
}
