<?php

declare(strict_types=1);

namespace In2code\T3AM\Domain\Repository;

use Doctrine\DBAL\Exception;
use In2code\T3AM\Domain\Factory\ImageFactory;
use In2code\T3AM\Domain\Model\Image;
use In2code\T3AM\Domain\Model\User;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageRepository
{
    protected ConnectionPool $connectionPool;

    protected ImageFactory $factory;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->factory = GeneralUtility::makeInstance(ImageFactory::class);
    }

    /**
     * @throws Exception
     */
    public function findImageByUser(User $user): ?Image
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $fileObjects = $fileRepository->findByRelation('be_users', 'avatar', $user->getUid());

        if (!empty($fileObjects) && count($fileObjects) == 1) {
            return $this->factory->fromFileReference($fileObjects[0]);
        } else {
            return null;
        }
    }
}
