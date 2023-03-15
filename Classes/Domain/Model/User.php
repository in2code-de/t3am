<?php

declare(strict_types=1);
namespace In2code\T3AM\Domain\Model;

use JsonSerializable;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function time;

class User implements JsonSerializable
{
    /** @var int */
    protected int $uid;

    /** @var int */
    protected int $tstamp;

    /** @var int */
    protected int $crdate;

    /** @var int */
    protected int $deleted;

    /** @var int */
    protected int $disable;

    /** @var int */
    protected int $starttime;

    /** @var int */
    protected int $endtime;

    /** @var string */
    protected string $description;

    /** @var string */
    protected string $username;

    /** @var int */
    protected int $avatar;

    /** @var string */
    protected string $password;

    /** @var bool */
    protected int $admin;

    /** @var string */
    protected string $lang;

    /** @var string */
    protected string $email;

    /** @var string */
    protected string $realName;

    public function __construct(
        int $uid,
        int $tstamp,
        int $crdate,
        int $deleted,
        int $disable,
        int $starttime,
        int $endtime,
        string $description,
        string $username,
        int $avatar,
        string $password,
        int $admin,
        string $lang,
        string $email,
        string $realName
    ) {
        $this->uid = $uid;
        $this->tstamp = $tstamp;
        $this->crdate = $crdate;
        $this->deleted = $deleted;
        $this->disable = $disable;
        $this->starttime = $starttime;
        $this->endtime = $endtime;
        $this->description = $description;
        $this->username = $username;
        $this->avatar = $avatar;
        $this->password = $password;
        $this->admin = $admin;
        $this->lang = $lang;
        $this->email = $email;
        $this->realName = $realName;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    public function isDeleted(): int
    {
        return $this->deleted;
    }

    public function isDisable(): int
    {
        return $this->disable;
    }

    public function getStarttime(): int
    {
        return $this->starttime;
    }

    public function isAfterStarttime(): bool
    {
        return empty($this->starttime) || time() > $this->starttime;
    }

    public function getEndtime(): int
    {
        return $this->endtime;
    }

    public function isBeforeEndtime(): bool
    {
        return empty($this->endtime) || time() < $this->endtime;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isBetweenStartAndEndTime(): bool
    {
        return $this->isAfterStarttime() && $this->isBeforeEndtime();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAvatar(): int
    {
        return $this->avatar;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function isAdmin(): int
    {
        return $this->admin;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRealName(): string
    {
        return $this->realName;
    }

    public function isActive(): bool
    {
        return !$this->isDeleted()
               && !$this->isDisable()
               && $this->isBetweenStartAndEndTime();
    }

    /**
     * @throws InvalidPasswordHashException
     */
    public function isValidPassword(string $password): bool
    {
        $passwordHashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
        $hashingInstance = $passwordHashFactory->get($this->password, 'BE');
        return $hashingInstance->checkPassword($password, $this->password);
    }

    public function jsonSerialize(): array
    {
        return [
            'tstamp' => $this->tstamp,
            'username' => $this->username,
            'description' => $this->description,
            'avatar' => $this->avatar,
            'password' => $this->password,
            'admin' => $this->admin,
            'disable' => $this->disable,
            'starttime' => $this->starttime,
            'endtime' => $this->endtime,
            'lang' => $this->lang,
            'email' => $this->email,
            'crdate' => $this->crdate,
            'realName' => $this->realName,
            'deleted' => $this->deleted,
        ];
    }

    public function isNewerThan(User $user): bool
    {
        if (!empty(array_diff($this->jsonSerialize(), $user->jsonSerialize()))) {
            return $this->tstamp >= $user->getTstamp();
        }
        return false;
    }
}
