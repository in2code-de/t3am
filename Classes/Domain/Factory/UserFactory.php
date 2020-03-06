<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Factory;

use In2code\T3AM\Domain\Collection\UserCollection;
use In2code\T3AM\Domain\Model\User;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserFactory
{
    public function fromRows(array $rows): UserCollection
    {
        $users = [];
        foreach ($rows as $row) {
            $users[] = $this->fromRow($row);
        }
        return GeneralUtility::makeInstance(UserCollection::class, $users);
    }

    public function fromRow(array $row): User
    {
        return GeneralUtility::makeInstance(
            User::class,
            $row['uid'],
            $row['tstamp'],
            $row['crdate'],
            $row['deleted'],
            $row['disable'],
            $row['starttime'],
            $row['endtime'],
            $row['description'] ?? '',
            $row['username'],
            $row['avatar'],
            $row['password'],
            $row['admin'],
            $row['lang'],
            $row['email'],
            $row['disableIPlock'],
            $row['realName']
        );
    }
}
