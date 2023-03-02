<?php

declare(strict_types=1);
namespace In2code\T3AM\Domain\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use In2code\T3AM\Domain\Factory\UserFactory;
use In2code\T3AM\Domain\Model\User;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserRepository
{
    public const TABLE_BE_USERS = 'be_users';

    /** @var ConnectionPool */
    protected $connectionPool;

    /** @var UserFactory */
    protected $factory;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->factory = GeneralUtility::makeInstance(UserFactory::class);
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function findUsersByUsername(string $username)
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BE_USERS);
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from(self::TABLE_BE_USERS)
              ->where($query->expr()->eq('username', $query->createNamedParameter($username)));
        return $this->factory->fromRows($query->execute()->fetchAllAssociative());
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function updateLocalUserWithNewUser(User $user): bool
    {
        $localUsers = $this->findUsersByUsername($user->getUsername());
        if ($localUsers->count() === 0) {
            return $this->add($user);
        }
        $localUser = $localUsers->getActive()->getFirst() ?? $localUsers->getFirst();

        if ($user->isNewerThan($localUser)) {
            return $this->update($localUser, $user);
        }

        return true;
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function getFirstActiveUserRaw(string $username): ?array
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BE_USERS);
        $query->select('*')
              ->from(self::TABLE_BE_USERS)
              ->where($query->expr()->eq('username', $query->createNamedParameter($username)));
        $statement = $query->execute();
        if ($statement->rowCount() < 1) {
            return null;
        }
        return $statement->fetchAssociative();
    }

    /**
     * @throws DBALException
     */
    protected function add(User $user): bool
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BE_USERS);
        $query->insert(self::TABLE_BE_USERS)
              ->values($this->factory->toDatabaseConformArray($user));
        return 1 === $query->execute();
    }

    /**
     * @throws DBALException
     */
    protected function update(User $localUser, User $user): bool
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BE_USERS);
        $query->update(self::TABLE_BE_USERS)
              ->where($query->expr()->eq('uid', $query->createNamedParameter($localUser->getUid())));
        foreach ($this->factory->toDatabaseConformArray($user) as $field => $value) {
            $query->set($field, $value);
        }
        return 1 === $query->execute();
    }

    /**
     * @throws DBALException
     */
    public function deleteByUsername(string $username): int
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BE_USERS);
        $query->delete(self::TABLE_BE_USERS)
              ->where($query->expr()->eq('username', $query->createNamedParameter($username)));
        return $query->execute();
    }
}
