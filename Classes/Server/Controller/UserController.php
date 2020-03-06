<?php
declare(strict_types=1);
namespace In2code\T3AM\Server\Controller;

use In2code\T3AM\Domain\Model\Image;
use In2code\T3AM\Domain\Model\User;
use In2code\T3AM\Domain\Repository\ImageRepository;
use In2code\T3AM\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserController
{
    public function getUserState(string $user): string
    {
        $userRepo = GeneralUtility::makeInstance(UserRepository::class);
        $userCollection = $userRepo->findUsersByUsername($user);
        return $userCollection->getUserState();
    }

    public function getUser(string $user): ?User
    {
        $userRepo = GeneralUtility::makeInstance(UserRepository::class);
        $userCollection = $userRepo->findUsersByUsername($user);
        return $userCollection->getActive()->getFirst();
    }

    public function getUserImage(string $user): ?Image
    {
        $userRepo = GeneralUtility::makeInstance(UserRepository::class);
        $userCollection = $userRepo->findUsersByUsername($user);
        $user = $userCollection->getActive()->getFirst();

        if (null === $user) {
            return null;
        }

        $imageRepo = GeneralUtility::makeInstance(ImageRepository::class);
        return $imageRepo->findImageByUser($user);
    }
}
