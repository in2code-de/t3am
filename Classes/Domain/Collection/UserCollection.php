<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Collection;

use ArrayIterator;
use Countable;
use In2code\T3AM\Domain\Model\User;
use IteratorAggregate;
use LogicException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserCollection implements IteratorAggregate, Countable
{
    public const USER_UNKNOWN = 'unknown';
    public const USER_DELETED = 'deleted';
    public const USER_OKAY = 'okay';

    /**
     * @var User[]
     */
    protected $users = [];

    public function __construct(array $users)
    {
        foreach ($users as $user) {
            $this->add($user);
        }
    }

    public function add(User $user)
    {
        $this->users[] = $user;
    }

    public function getFirst(): ?User
    {
        return reset($this->users);
    }

    public function getUserState()
    {
        if ($this->count() === 0) {
            return self::USER_UNKNOWN;
        }
        if ($this->getActive()->count() >= 1) {
            return self::USER_OKAY;
        }
        if ($this->getInactive()->count() >= 1) {
            return self::USER_DELETED;
        }
        throw new LogicException('The user collection did not partition correctly');
    }

    public function getIterator()
    {
        $users = [];
        foreach ($this->users as $user) {
            $users[] = clone $user;
        }
        return new ArrayIterator($users);
    }

    public function count()
    {
        return count($this->users);
    }

    public function getInactive(): UserCollection
    {
        $users = [];
        foreach ($this->users as $user) {
            if (!$user->isActive()) {
                $users[] = $user;
            }
        }
        return GeneralUtility::makeInstance(UserCollection::class, $users);
    }

    public function getActive(): UserCollection
    {
        $users = [];
        foreach ($this->users as $user) {
            if ($user->isActive()) {
                $users[] = $user;
            }
        }
        return GeneralUtility::makeInstance(UserCollection::class, $users);
    }
}
