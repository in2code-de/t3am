<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Repository;

use In2code\T3AM\Domain\Factory\ImageFactory;
use In2code\T3AM\Domain\Model\Image;
use In2code\T3AM\Domain\Model\User;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageRepository
{
    protected const TABLE_SYS_FILE_REFERENCE = 'sys_file_reference';

    /** @var ConnectionPool */
    protected $connectionPool;

    /** @var ImageFactory */
    protected $factory;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->factory = GeneralUtility::makeInstance(ImageFactory::class);
    }

    public function findImageByUser(User $user): ?Image
    {
        $imageFileUid = $this->getImageFileUid($user);
        if (null !== $imageFileUid) {
            try {
                $fileUid = ResourceFactory::getInstance()->getFileObject($imageFileUid);
                return $this->factory->createFromFile($fileUid);
            } catch (FileDoesNotExistException $e) {
            }
        }
        return null;
    }

    protected function getImageFileUid(User $user): ?int
    {
        /** @see \TYPO3\CMS\Backend\Backend\Avatar\DefaultAvatarProvider::getAvatarFileUid */
        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE_SYS_FILE_REFERENCE);
        $query->select('uid_local')
              ->from(self::TABLE_SYS_FILE_REFERENCE)
              ->where(
                  $query->expr()->eq('tablenames', $query->createNamedParameter('be_users')),
                  $query->expr()->eq('fieldname', $query->createNamedParameter('avatar')),
                  $query->expr()->eq('table_local', $query->createNamedParameter('sys_file')),
                  $query->expr()->eq('uid_foreign', $query->createNamedParameter($user->getUid()))
              );
        $statement = $query->execute();
        if ($statement->rowCount() === 0) {
            return null;
        }
        return (int)$statement->fetchColumn();
    }
}
