<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\TestHelper;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * 测试用户类
 * 用于单元测试中模拟用户对象
 */
class TestUser implements UserInterface
{
    public function __construct(private int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }
}
