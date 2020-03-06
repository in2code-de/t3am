<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Model;

use JsonSerializable;
use TYPO3\CMS\Core\Resource\AbstractFile;
use function base64_encode;

class Image implements JsonSerializable
{
    /** @var AbstractFile */
    protected $file;

    public function __construct(AbstractFile $file)
    {
        $this->file = $file;
    }

    public function jsonSerialize()
    {
        return [
            'identifier' => $this->file->getName(),
            'b64content' => base64_encode($this->file->getContents()),
        ];
    }
}
