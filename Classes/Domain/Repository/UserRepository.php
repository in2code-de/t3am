<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Repository;

use In2code\T3AM\Domain\Factory\UserFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserRepository
{
    protected const TABLE_BE_USERS = 'be_users';

    protected $connectionPool;
    protected $factory;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->factory = GeneralUtility::makeInstance(UserFactory::class);
    }

    public function findUsersByUsername(string $username)
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BE_USERS);
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from(self::TABLE_BE_USERS)
              ->where($query->expr()->eq('username', $query->createNamedParameter($username)));
        return $this->factory->createBatch($query->execute()->fetchAll());
    }
}
