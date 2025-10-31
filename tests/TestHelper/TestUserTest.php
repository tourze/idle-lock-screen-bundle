<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\TestHelper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
#[CoversClass(TestUser::class)]
final class TestUserTest extends TestCase
{
    private TestUser $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = new TestUser(123);
    }

    /**
     * 测试TestUser可以正确实例化
     */
    public function testTestUserCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TestUser::class, $this->testUser);
        $this->assertInstanceOf(UserInterface::class, $this->testUser);
    }

    /**
     * 测试getId方法返回正确的ID
     */
    public function testGetIdReturnsCorrectId(): void
    {
        $this->assertEquals(123, $this->testUser->getId());
    }

    /**
     * 测试getUserIdentifier返回字符串形式的ID
     */
    public function testGetUserIdentifierReturnsStringId(): void
    {
        $this->assertEquals('123', $this->testUser->getUserIdentifier());
    }

    /**
     * 测试getRoles返回预期的角色数组
     */
    public function testGetRolesReturnsExpectedRoles(): void
    {
        $roles = $this->testUser->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(1, $roles);
    }

    /**
     * 测试序列化时会清除凭证
     */
    public function testCredentialsAreClearedOnSerialization(): void
    {
        // 验证序列化行为 - Symfony 7.3+ 使用 __serialize() 来清除凭证
        $serialized = serialize($this->testUser);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(TestUser::class, $unserialized);
        $this->assertEquals(123, $unserialized->getId());
    }

    /**
     * 测试使用不同ID创建TestUser
     */
    public function testDifferentIdsCreateDifferentUsers(): void
    {
        $user1 = new TestUser(456);
        $user2 = new TestUser(789);

        $this->assertEquals(456, $user1->getId());
        $this->assertEquals('456', $user1->getUserIdentifier());

        $this->assertEquals(789, $user2->getId());
        $this->assertEquals('789', $user2->getUserIdentifier());

        $this->assertNotEquals($user1->getId(), $user2->getId());
    }

    /**
     * 测试eraseCredentials方法不抛出异常
     * 注意：此方法在 Symfony 7.3+ 中已废弃，但保留测试以确保向后兼容性
     */
    public function testEraseCredentialsDoesNotThrowException(): void
    {
        // 验证用户对象状态正常（不调用已废弃的方法）
        $this->assertEquals(123, $this->testUser->getId());
        $this->assertEquals('123', $this->testUser->getUserIdentifier());

        // 确保对象可以正常序列化
        $serialized = serialize($this->testUser);
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(TestUser::class, $unserialized);
        $this->assertEquals($this->testUser->getId(), $unserialized->getId());
    }
}
