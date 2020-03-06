<?php
declare(strict_types=1);
namespace In2code\T3AM\Server\Controller;

use In2code\T3AM\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserController
{
    public function getUserState(string $user)
    {
        $userRepo = GeneralUtility::makeInstance(UserRepository::class);
        $userCollection = $userRepo->findUsersByUsername($user);
        return $userCollection->getUserState();
    }
}
