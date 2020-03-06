<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Model;

use function time;

class User
{
    /** @var int */
    protected $uid;

    /** @var bool */
    protected $deleted;

    /** @var bool */
    protected $disable;

    /** @var int */
    protected $starttime;

    /** @var int */
    protected $endtime;

    /** @var string */
    protected $username;

    /** @var int */
    protected $avatar;

    /** @var string */
    protected $password;

    /** @var bool */
    protected $admin;

    /** @var string */
    protected $email;

    /** @var string */
    protected $realName;

    public function __construct(
        int $uid,
        bool $deleted,
        bool $disable,
        int $starttime,
        int $endtime,
        string $username,
        int $avatar,
        string $password,
        bool $admin,
        string $email,
        string $realName
    ) {
        $this->uid = $uid;
        $this->deleted = $deleted;
        $this->disable = $disable;
        $this->starttime = $starttime;
        $this->endtime = $endtime;
        $this->username = $username;
        $this->avatar = $avatar;
        $this->password = $password;
        $this->admin = $admin;
        $this->email = $email;
        $this->realName = $realName;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function isDisable(): bool
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

    public function isAdmin(): bool
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

    public function isActive()
    {
        return !$this->isDeleted()
               && !$this->isDisable()
               && $this->isBetweenStartAndEndTime();
    }
}
