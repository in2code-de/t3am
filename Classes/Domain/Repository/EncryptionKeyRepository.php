<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Repository;

use In2code\T3AM\Domain\Factory\EncryptionKeyFactory;
use In2code\T3AM\Domain\Model\EncryptionKey;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class EncryptionKeyRepository
{
    public const TABLE_TX_T3AM_ENCRYPTION_KEY = 'tx_t3am_encryption_key';

    /** @var ConnectionPool */
    protected $connectionPool;

    /** @var EncryptionKeyFactory */
    protected $factory;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->factory = GeneralUtility::makeInstance(EncryptionKeyFactory::class);
    }

    public function findAndDeleteOneByUid(int $uid): ?EncryptionKey
    {
        $token = $this->findOneByUid($uid);
        $this->deleteOneByUid($uid);
        return $token;
    }

    public function persist(string $privateKey, string $publicKey): ?EncryptionKey
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_TX_T3AM_ENCRYPTION_KEY);
        $query = $connection->createQueryBuilder();
        $query->insert(EncryptionKeyRepository::TABLE_TX_T3AM_ENCRYPTION_KEY)
              ->values(['private_key' => $privateKey, 'public_key' => $publicKey]);
        if (1 !== $query->execute()) {
            return null;
        }
        $uid = $connection->lastInsertId(EncryptionKeyRepository::TABLE_TX_T3AM_ENCRYPTION_KEY);
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            return null;
        }
        return $this->findOneByUid((int)$uid);
    }

    protected function findOneByUid(int $uid): ?EncryptionKey
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_ENCRYPTION_KEY);
        $query->select('*')
              ->from(self::TABLE_TX_T3AM_ENCRYPTION_KEY)
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
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_ENCRYPTION_KEY);
        $query->delete(self::TABLE_TX_T3AM_ENCRYPTION_KEY)
              ->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
        return 1 === $query->execute();
    }
}
