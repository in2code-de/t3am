<?php
declare(strict_types=1);
namespace In2code\T3AM\Server\Controller;

use In2code\T3AM\Domain\Model\Image;
use In2code\T3AM\Domain\Model\User;
use In2code\T3AM\Domain\Repository\DecryptionKeyRepository;
use In2code\T3AM\Domain\Repository\ImageRepository;
use In2code\T3AM\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function base64_decode;
use function urldecode;

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

    public function authUser(string $user, string $password, int $encryptionId): bool
    {
        $encryptionKeyPairRepo = GeneralUtility::makeInstance(DecryptionKeyRepository::class);
        $encryptionKeyPair = $encryptionKeyPairRepo->findAndDeleteOneByUid($encryptionId);

        if (null === $encryptionKeyPair) {
            return false;
        }

        $users = GeneralUtility::makeInstance(UserRepository::class)->findUsersByUsername($user);
        $userObject = $users->getActive()->getFirst();

        if (null === $userObject) {
            return false;
        }

        $password = base64_decode(urldecode($password));

        $decryptedPassword = $encryptionKeyPair->decrypt($password);
        return $userObject->isValidPassword($decryptedPassword);
    }
}
