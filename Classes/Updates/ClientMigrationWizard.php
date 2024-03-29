<?php

declare(strict_types=1);

namespace In2code\T3AM\Updates;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

use function sprintf;

class ClientMigrationWizard implements UpgradeWizardInterface, ChattyInterface
{
    protected const TABLE_CLIENT_LEGACY = 'tx_t3amserver_client';
    protected const TABLE_CLIENT_NEW = 'tx_t3am_client';

    protected ConnectionPool $connectionPool;

    protected OutputInterface $output;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function getIdentifier(): string
    {
        return 'tx_t3am_client_migration';
    }

    public function getTitle(): string
    {
        return 'T3AM Client record migration';
    }

    public function getDescription(): string
    {
        return 'Migrates all T3AM client records to the new table';
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function executeUpdate(): bool
    {
        if (!$this->tableExists(self::TABLE_CLIENT_LEGACY)) {
            $this->output->writeln('The legacy table was deleted. Can not migrate client records.');
            return false;
        }

        $legacyClientStatement = $this->selectLegacyClients();

        if (0 === $legacyClientStatement->rowCount()) {
            $this->output->writeln('There are no client records left to migrate.');
            return false;
        }

        while ($row = $legacyClientStatement->fetchAssociative()) {
            if (!$this->clientExistsInNewTable($row['token'])) {
                $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CLIENT_NEW);
                if (1 !== $query->insert(self::TABLE_CLIENT_NEW)->values($row)->executeStatement()) {
                    $this->output->writeln(sprintf('Failed to migrate client with legacy uid %d', $row['uid']));
                }
            }
        }

        $this->output->writeln(
            sprintf('Migration successful. You may now delete the old table "%s"', self::TABLE_CLIENT_LEGACY)
        );
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function updateNecessary(): bool
    {
        if (!$this->tableExists(self::TABLE_CLIENT_LEGACY)) {
            // Table does not exist. Nothing to migrate.
            return false;
        }

        $legacyClientStatement = $this->selectLegacyClients();

        if (0 === $legacyClientStatement->rowCount()) {
            // Table is empty. Nothing to migrate.
            return false;
        }

        while ($row = $legacyClientStatement->fetchAssociative()) {
            if (!$this->clientExistsInNewTable($row['token'])) {
                // Client is missing in new table. Migrate.
                return true;
            }
        }

        // All clients exist in the new table. Nothing to migrate.
        return false;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @throws Exception
     */
    protected function tableExists(string $table): bool
    {
        $connection = $this->connectionPool->getConnectionForTable($table);
        $schemaManager = $connection->createSchemaManager();
        return $schemaManager->tablesExist($table);
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }


    /**
     * @return Result
     */
    protected function selectLegacyClients(): Result
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CLIENT_LEGACY);
        $query->select('*')
            ->from(self::TABLE_CLIENT_LEGACY);
        return $query->executeQuery();
    }

    /**
     * @param string $token
     * @return bool
     * @throws Exception
     */
    protected function clientExistsInNewTable(string $token): bool
    {
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CLIENT_NEW);
        $query->getRestrictions()->removeAll();
        $query->count('*')
            ->from(self::TABLE_CLIENT_NEW)
            ->where($query->expr()->eq('token', $query->createNamedParameter($token)));
        return 1 === $query->executeQuery()->fetchOne();
    }
}
