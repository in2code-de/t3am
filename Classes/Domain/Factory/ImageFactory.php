<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Factory;

use In2code\T3AM\Domain\Model\Image;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageFactory
{
    public function createFromFile(AbstractFile $file): Image
    {
        return GeneralUtility::makeInstance(Image::class, $file);
    }
}
