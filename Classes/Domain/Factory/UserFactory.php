<?php

declare(strict_types=1);

namespace In2code\T3AM\Domain\Factory;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use In2code\T3AM\Domain\Collection\UserCollection;
use In2code\T3AM\Domain\Model\User;
use In2code\T3AM\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function settype;

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
            $row['realName']
        );
    }

    /**
     * @throws Exception
     */
    public function toDatabaseConformArray(User $user): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable(UserRepository::TABLE_BE_USERS);
        $columns = $connection->createSchemaManager()->listTableColumns(UserRepository::TABLE_BE_USERS);

        $array = [];

        foreach ($user->jsonSerialize() as $field => $value) {
            if (isset($columns[strtolower($field)])) {
                // Risky attempt here but as long as all fields are string or integer types we'll be fine
                match ($columns[strtolower($field)]->getType()->getName()) {
                    Types::BIGINT, Types::BINARY, Types::INTEGER, Types::DECIMAL, Types::SMALLINT => settype($value, 'int'),
                    Types::FLOAT => settype($value, 'float'),
                    Types::TEXT, Types::STRING => settype($value, 'string')
                };
                $array[$field] = $value;
            }
        }

        return $array;
    }
}
