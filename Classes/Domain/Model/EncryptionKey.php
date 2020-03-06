<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Model;

use function base64_decode;
use function openssl_private_decrypt;

class EncryptionKey
{
    /** @var int */
    protected $uid;

    /** @var string */
    protected $keyValue;

    public function __construct(int $uid, string $keyValue)
    {
        $this->uid = $uid;
        $this->keyValue = $keyValue;
    }

    public function decrypt(string $input): ?string
    {
        $privateKey = base64_decode($this->keyValue);

        $output = '';
        if (!@openssl_private_decrypt($input, $output, $privateKey)) {
            return null;
        }
        return $output;
    }
}
