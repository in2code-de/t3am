<?php

declare(strict_types=1);

namespace In2code\T3AM\Domain\Factory;

use In2code\T3AM\Domain\Model\Image;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageFactory
{
    public function fromFile(AbstractFile $file): Image
    {
        return GeneralUtility::makeInstance(Image::class, $file);
    }

    public function fromFileReference(FileReference $fileReference): Image
    {
        return GeneralUtility::makeInstance(Image::class, $fileReference->getOriginalFile());
    }
}
