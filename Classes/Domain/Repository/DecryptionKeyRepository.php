<?php

declare(strict_types=1);

namespace In2code\T3AM\Domain\Repository;

use Doctrine\DBAL\Exception;
use In2code\T3AM\Domain\Factory\DecryptionKeyFactory;
use In2code\T3AM\Domain\Model\DecryptionKey;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class DecryptionKeyRepository
{
    final public const TABLE_TX_T3AM_DECRYPTION_KEY = 'tx_t3am_decryption_key';

    protected ConnectionPool $connectionPool;

    protected DecryptionKeyFactory $factory;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->factory = GeneralUtility::makeInstance(DecryptionKeyFactory::class);
    }

    /**
     * @throws Exception
     */
    public function findAndDeleteOneByUid(int $uid): ?DecryptionKey
    {
        $token = $this->findOneByUid($uid);
        $this->deleteOneByUid($uid);
        return $token;
    }

    /**
     * @throws Exception
     */
    public function persist(string $privateKey): ?DecryptionKey
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_TX_T3AM_DECRYPTION_KEY);
        $query = $connection->createQueryBuilder();
        $query->insert(DecryptionKeyRepository::TABLE_TX_T3AM_DECRYPTION_KEY)
            ->values(['private_key' => $privateKey]);
        if (1 !== $query->executeStatement()) {
            return null;
        }
        $uid = $connection->lastInsertId(DecryptionKeyRepository::TABLE_TX_T3AM_DECRYPTION_KEY);
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            return null;
        }
        return $this->findOneByUid((int)$uid);
    }

    /**
     * @throws Exception
     */
    protected function findOneByUid(int $uid): ?DecryptionKey
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_DECRYPTION_KEY);
        $query->select('*')
            ->from(self::TABLE_TX_T3AM_DECRYPTION_KEY)
            ->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
        $statement = $query->executeQuery();
        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetchAssociative();
        return $this->factory->fromRow($row);
    }

    protected function deleteOneByUid(int $uid): bool
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_DECRYPTION_KEY);
        $query->delete(self::TABLE_TX_T3AM_DECRYPTION_KEY)
            ->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
        return 1 === $query->executeStatement();
    }
}
