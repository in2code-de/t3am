<?php
declare(strict_types=1);
namespace In2code\T3AM\Domain\Model;

use JsonSerializable;
use function time;

class User implements JsonSerializable
{
    /** @var int */
    protected $uid;

    /** @var int */
    protected $tstamp;

    /** @var int */
    protected $crdate;

    /** @var bool */
    protected $deleted;

    /** @var bool */
    protected $disable;

    /** @var int */
    protected $starttime;

    /** @var int */
    protected $endtime;

    /** @var string */
    protected $description;

    /** @var string */
    protected $username;

    /** @var int */
    protected $avatar;

    /** @var string */
    protected $password;

    /** @var bool */
    protected $admin;

    /** @var string */
    protected $lang;

    /** @var string */
    protected $email;

    /** @var bool */
    protected $disableIPlock;

    /** @var string */
    protected $realName;

    public function __construct(
        int $uid,
        int $tstamp,
        int $crdate,
        bool $deleted,
        bool $disable,
        int $starttime,
        int $endtime,
        string $description,
        string $username,
        int $avatar,
        string $password,
        bool $admin,
        string $lang,
        string $email,
        string $disableIPlock,
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
        $this->disableIPlock = $disableIPlock;
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

    public function isActive(): bool
    {
        return !$this->isDeleted()
               && !$this->isDisable()
               && $this->isBetweenStartAndEndTime();
    }

    public function jsonSerialize()
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
            'disableIPlock' => $this->disableIPlock,
            'deleted' => $this->deleted,
        ];
    }
}
