<?php

namespace Tourze\IdleLockScreenBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * 锁定记录实体
 * 记录用户的锁定和解锁操作历史
 */
#[ORM\Entity]
#[ORM\Table(name: 'idle_lock_record')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_id')]
#[ORM\Index(columns: ['session_id'], name: 'idx_session_id')]
#[ORM\Index(columns: ['action_type'], name: 'idx_action_type')]
#[ORM\Index(columns: ['created_at'], name: 'idx_created_at')]
#[ORM\Index(columns: ['user_id', 'session_id'], name: 'idx_user_session')]
class LockRecord
{
    public const ACTION_LOCKED = 'locked';
    public const ACTION_UNLOCKED = 'unlocked';
    public const ACTION_TIMEOUT = 'timeout';
    public const ACTION_BYPASS_ATTEMPT = 'bypass_attempt';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * 用户ID
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $userId = null;

    /**
     * 会话ID
     */
    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $sessionId;

    /**
     * 操作类型：locked, unlocked, timeout, bypass_attempt
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $actionType;

    /**
     * 触发锁定的路由
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $route;

    /**
     * 用户IP地址
     */
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    /**
     * 用户代理信息
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    /**
     * 额外的上下文信息（JSON格式）
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = null;

    /**
     * 创建时间
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): self
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isLockAction(): bool
    {
        return $this->actionType === self::ACTION_LOCKED;
    }

    public function isUnlockAction(): bool
    {
        return $this->actionType === self::ACTION_UNLOCKED;
    }

    public function isTimeoutAction(): bool
    {
        return $this->actionType === self::ACTION_TIMEOUT;
    }

    public function isBypassAttempt(): bool
    {
        return $this->actionType === self::ACTION_BYPASS_ATTEMPT;
    }
}
