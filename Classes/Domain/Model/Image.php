<?php

declare(strict_types=1);

namespace In2code\T3AM\Domain\Model;

use JsonSerializable;
use TYPO3\CMS\Core\Resource\AbstractFile;

use function base64_encode;

class Image implements JsonSerializable
{
    public function __construct(
        protected AbstractFile $file)
    {}

    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->file->getName(),
            'b64content' => base64_encode($this->file->getContents()),
        ];
    }
}
