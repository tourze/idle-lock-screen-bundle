<?php

namespace Tourze\IdleLockScreenBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\IdleLockScreenBundle\Enum\ActionType;
use Tourze\IdleLockScreenBundle\Repository\LockRecordRepository;

/**
 * 锁定记录实体
 * 记录用户的锁定和解锁操作历史
 */
#[ORM\Entity(repositoryClass: LockRecordRepository::class)]
#[ORM\Table(name: 'idle_lock_record', options: ['comment' => '无操作锁定记录表'])]
#[ORM\Index(name: 'idle_lock_record_idx_user_session', columns: ['user_id', 'session_id'])]
class LockRecord implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null; // @phpstan-ignore-line property.unusedType (Doctrine auto-assigns after persist)

    /**
     * 关联用户
     */
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?UserInterface $user = null;

    #[ORM\Column(type: Types::STRING, length: 128, options: ['comment' => '会话ID'])]
    #[Assert\NotBlank(message: '会话ID不能为空')]
    #[Assert\Length(max: 128, maxMessage: '会话ID长度不能超过{{ limit }}个字符')]
    #[IndexColumn]
    private string $sessionId;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ActionType::class, options: ['comment' => '操作类型'])]
    #[Assert\NotNull(message: '操作类型不能为空')]
    #[Assert\Choice(callback: [ActionType::class, 'cases'], message: '无效的操作类型')]
    #[IndexColumn]
    private ActionType $actionType;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '触发锁定的路由'])]
    #[Assert\NotBlank(message: '路由不能为空')]
    #[Assert\Length(max: 255, maxMessage: '路由长度不能超过{{ limit }}个字符')]
    private string $route;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true, options: ['comment' => '用户IP地址'])]
    #[Assert\Length(max: 45, maxMessage: 'IP地址长度不能超过{{ limit }}个字符')]
    #[Assert\Ip(message: '无效的IP地址格式')]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户代理信息'])]
    #[Assert\Length(max: 65535, maxMessage: '用户代理信息长度不能超过{{ limit }}个字符')]
    private ?string $userAgent = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '额外的上下文信息'])]
    #[Assert\Type(type: 'array', message: '上下文信息必须是数组类型')]
    private ?array $context = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    #[IndexColumn]
    private \DateTimeImmutable $createTime;

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getActionType(): ActionType
    {
        return $this->actionType;
    }

    public function setActionType(ActionType $actionType): void
    {
        $this->actionType = $actionType;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): void
    {
        $this->route = $route;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed>|null $context
     */
    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    /**
     * 获取用于显示的上下文信息（JSON字符串格式）
     * 这是一个虚拟字段，用于 EasyAdmin 显示
     */
    public function getContextForDisplay(): string
    {
        if (null === $this->context) {
            return '';
        }

        $encoded = json_encode($this->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return false !== $encoded ? $encoded : '';
    }

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function isLockAction(): bool
    {
        return ActionType::LOCKED === $this->actionType;
    }

    public function isUnlockAction(): bool
    {
        return ActionType::UNLOCKED === $this->actionType;
    }

    public function isTimeoutAction(): bool
    {
        return ActionType::TIMEOUT === $this->actionType;
    }

    public function isBypassAttempt(): bool
    {
        return ActionType::BYPASS_ATTEMPT === $this->actionType;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s - %s',
            $this->actionType->value,
            $this->route,
            $this->createTime->format('Y-m-d H:i:s')
        );
    }
}
