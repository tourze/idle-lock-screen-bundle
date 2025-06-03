<?php

namespace Tourze\IdleLockScreenBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * 锁定配置实体
 * 用于配置哪些路由需要进行无操作锁定检查
 */
#[ORM\Entity]
#[ORM\Table(name: 'idle_lock_configuration')]
#[ORM\Index(name: 'idx_route_pattern', columns: ['route_pattern'])]
#[ORM\Index(name: 'idx_is_enabled', columns: ['is_enabled'])]
class LockConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * 路由模式，支持通配符和正则表达式
     * 例如：/billing/*, /account/bill/*, ^/admin/.*
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $routePattern;

    /**
     * 无操作超时时间（秒）
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $timeoutSeconds = 60;

    /**
     * 是否启用此配置
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isEnabled = true;

    /**
     * 配置描述
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $description = null;

    /**
     * 创建时间
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * 更新时间
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoutePattern(): string
    {
        return $this->routePattern;
    }

    public function setRoutePattern(string $routePattern): self
    {
        $this->routePattern = $routePattern;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function setTimeoutSeconds(int $timeoutSeconds): self
    {
        $this->timeoutSeconds = $timeoutSeconds;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
