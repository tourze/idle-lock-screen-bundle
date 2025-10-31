<?php

namespace Tourze\IdleLockScreenBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\IdleLockScreenBundle\Repository\LockConfigurationRepository;

/**
 * 锁定配置实体
 * 用于配置哪些路由需要进行无操作锁定检查
 */
#[ORM\Entity(repositoryClass: LockConfigurationRepository::class)]
#[ORM\Table(name: 'idle_lock_configuration', options: ['comment' => '无操作锁定配置表'])]
class LockConfiguration implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null; // @phpstan-ignore-line property.unusedType (Doctrine auto-assigns after persist)

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '路由模式，支持通配符和正则表达式'])]
    #[Assert\NotBlank(message: '路由模式不能为空')]
    #[Assert\Length(max: 255, maxMessage: '路由模式长度不能超过{{ limit }}个字符')]
    #[IndexColumn]
    private string $routePattern;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '无操作超时时间（秒）'])]
    #[Assert\NotBlank(message: '超时时间不能为空')]
    #[Assert\Positive(message: '超时时间必须是正数')]
    #[Assert\Range(min: 1, max: 86400, notInRangeMessage: '超时时间必须在{{ min }}到{{ max }}秒之间')]
    private int $timeoutSeconds = 60;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用此配置'])]
    #[Assert\NotNull(message: '启用状态不能为空')]
    #[IndexColumn]
    private bool $isEnabled = true;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '配置描述'])]
    #[Assert\Length(max: 500, maxMessage: '描述长度不能超过{{ limit }}个字符')]
    private ?string $description = null;

    public function __construct()
    {
        $this->setCreateTime(new \DateTimeImmutable());
        $this->setUpdateTime(new \DateTimeImmutable());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoutePattern(): string
    {
        return $this->routePattern;
    }

    public function setRoutePattern(string $routePattern): void
    {
        $this->routePattern = $routePattern;
        $this->setUpdateTime(new \DateTimeImmutable());
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function setTimeoutSeconds(int $timeoutSeconds): void
    {
        $this->timeoutSeconds = $timeoutSeconds;
        $this->setUpdateTime(new \DateTimeImmutable());
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
        $this->setUpdateTime(new \DateTimeImmutable());
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->setUpdateTime(new \DateTimeImmutable());
    }

    public function __toString(): string
    {
        return sprintf('%s (超时: %ds)', $this->routePattern, $this->timeoutSeconds);
    }
}
