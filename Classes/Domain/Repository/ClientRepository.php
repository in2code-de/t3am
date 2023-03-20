<?php

declare(strict_types=1);

namespace In2code\T3AM\Domain\Repository;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClientRepository
{
    protected const TABLE_TX_T3AM_CLIENT = 'tx_t3am_client';

    protected ConnectionPool $connectionPool;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @throws Exception
     */
    public function countByToken(string $token): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $query = $connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_CLIENT);
        $query->count('*')
            ->from(self::TABLE_TX_T3AM_CLIENT)
            ->where($query->expr()->eq('token', $query->createNamedParameter($token)));

        return $query->executeQuery()->fetchOne();
    }
}
