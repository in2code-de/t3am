<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Repository;

use In2code\T3AM\Domain\Factory\EncryptionKeyFactory;
use In2code\T3AM\Domain\Model\EncryptionKey;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_ENCRYPTION_KEY);
        $query->select('*')
              ->from(self::TABLE_TX_T3AM_ENCRYPTION_KEY)
              ->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
        $statement = $query->execute();
        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch();

        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TX_T3AM_ENCRYPTION_KEY);
        $query->delete(self::TABLE_TX_T3AM_ENCRYPTION_KEY)
              ->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
        $query->execute();

        return $this->factory->create($row);
    }
}
