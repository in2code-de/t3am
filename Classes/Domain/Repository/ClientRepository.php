<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClientRepository
{
    protected const TABLE_TX_T3AM_CLIENT = 'tx_t3am_client';

    /** @var ConnectionPool */
    protected $connectionPool;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function countByToken(string $token): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $query = $connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_CLIENT);
        $query->count('*')
              ->from(self::TABLE_TX_T3AM_CLIENT)
              ->where($query->expr()->eq('token', $query->createNamedParameter($token)));
        $statement = $query->execute();
        return $statement->fetchColumn();
    }
}
