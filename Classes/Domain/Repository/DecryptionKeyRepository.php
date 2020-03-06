<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Repository;

use In2code\T3AM\Domain\Factory\DecryptionKeyFactory;
use In2code\T3AM\Domain\Model\DecryptionKey;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class DecryptionKeyRepository
{
    public const TABLE_TX_T3AM_DECRYPTION_KEY = 'tx_t3am_decryption_key';

    /** @var ConnectionPool */
    protected $connectionPool;

    /** @var DecryptionKeyFactory */
    protected $factory;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->factory = GeneralUtility::makeInstance(DecryptionKeyFactory::class);
    }

    public function findAndDeleteOneByUid(int $uid): ?DecryptionKey
    {
        $token = $this->findOneByUid($uid);
        $this->deleteOneByUid($uid);
        return $token;
    }

    public function persist(string $privateKey): ?DecryptionKey
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_TX_T3AM_DECRYPTION_KEY);
        $query = $connection->createQueryBuilder();
        $query->insert(DecryptionKeyRepository::TABLE_TX_T3AM_DECRYPTION_KEY)
              ->values(['private_key' => $privateKey]);
        if (1 !== $query->execute()) {
            return null;
        }
        $uid = $connection->lastInsertId(DecryptionKeyRepository::TABLE_TX_T3AM_DECRYPTION_KEY);
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            return null;
        }
        return $this->findOneByUid((int)$uid);
    }

    protected function findOneByUid(int $uid): ?DecryptionKey
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_DECRYPTION_KEY);
        $query->select('*')
              ->from(self::TABLE_TX_T3AM_DECRYPTION_KEY)
              ->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
        $statement = $query->execute();
        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch();
        return $this->factory->fromRow($row);
    }

    protected function deleteOneByUid(int $uid): bool
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_DECRYPTION_KEY);
        $query->delete(self::TABLE_TX_T3AM_DECRYPTION_KEY)
              ->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
        return 1 === $query->execute();
    }
}
