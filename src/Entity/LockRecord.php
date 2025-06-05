<?php

namespace Tourze\IdleLockScreenBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\IdleLockScreenBundle\Enum\ActionType;

/**
 * 锁定记录实体
 * 记录用户的锁定和解锁操作历史
 */
#[ORM\Entity]
#[ORM\Table(name: 'idle_lock_record')]
#[ORM\Index(name: 'idx_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_session_id', columns: ['session_id'])]
#[ORM\Index(name: 'idx_action_type', columns: ['action_type'])]
#[ORM\Index(name: 'idx_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_user_session', columns: ['user_id', 'session_id'])]
class LockRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * 关联用户
     */
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?UserInterface $user = null;

    /**
     * 会话ID
     */
    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $sessionId;

    /**
     * 操作类型枚举
     */
    #[ORM\Column(type: Types::STRING, length: 20, enumType: ActionType::class)]
    private ActionType $actionType;

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

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * 获取用户ID（兼容性方法）
     */
    public function getUserId(): ?int
    {
        if ($this->user === null) {
            return null;
        }

        // 尝试获取用户ID，支持不同的用户实现
        if (method_exists($this->user, 'getId')) {
            $id = call_user_func([$this->user, 'getId']);
            return is_int($id) ? $id : (is_numeric($id) ? (int) $id : null);
        }

        // 如果用户实现了 getUserIdentifier，尝试将其转换为数字
        $identifier = $this->user->getUserIdentifier();
        return is_numeric($identifier) ? (int) $identifier : null;
    }

    /**
     * 设置用户ID（兼容性方法，已废弃）
     * @deprecated 请使用 setUser() 方法
     */
    public function setUserId(?int $userId): self
    {
        // 为了向后兼容，保留此方法，但不做任何操作
        // 实际设置用户应该通过 setUser() 方法
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

    public function getActionType(): ActionType
    {
        return $this->actionType;
    }

    public function setActionType(ActionType $actionType): self
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
        return $this->actionType === ActionType::LOCKED;
    }

    public function isUnlockAction(): bool
    {
        return $this->actionType === ActionType::UNLOCKED;
    }

    public function isTimeoutAction(): bool
    {
        return $this->actionType === ActionType::TIMEOUT;
    }

    public function isBypassAttempt(): bool
    {
        return $this->actionType === ActionType::BYPASS_ATTEMPT;
    }
}
