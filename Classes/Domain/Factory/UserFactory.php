<?php

declare(strict_types=1);

namespace In2code\T3AM\Domain\Factory;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use In2code\T3AM\Domain\Collection\UserCollection;
use In2code\T3AM\Domain\Model\User;
use In2code\T3AM\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function settype;

class UserFactory
{
    protected array $columnBeUserTable;

    public function __construct()
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable(UserRepository::TABLE_BE_USERS);
        $this->columnBeUserTable = $connection->createSchemaManager()->listTableColumns(UserRepository::TABLE_BE_USERS);
    }

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
            $row['realName'],
        );
    }

    /**
     * @throws Exception
     */
    public function toDatabaseConformArray(User $user): array
    {
        $array = [];

        foreach ($user->jsonSerialize() as $field => $value) {
            $columnType = $this->getColumnTypeByField($field);
            if ($columnType !== null) {
                // Risky attempt here but as long as all fields are string or integer types we'll be fine
                match ($columnType) {
                    Types::BIGINT, Types::BINARY, Types::INTEGER, Types::DECIMAL, Types::SMALLINT
                        => settype($value, 'int'),
                    Types::FLOAT => settype($value, 'float'),
                    Types::TEXT, Types::STRING => settype($value, 'string'),
                    default => settype($value, 'string')
                };
                $array[$field] = $value;
            }
        }

        return $array;
    }

    protected function getColumnTypeByField(string $field)
    {
        foreach ($this->columnBeUserTable as $column) {
            /** @var Column $column */
            if ($column->getName() === $field) {
                return $column->getType()->getName();
            }
        }
        return null;
    }
}
