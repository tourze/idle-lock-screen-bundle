<?php

namespace Tourze\IdleLockScreenBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Enum\ActionType;

/**
 * 测试用户类
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

/**
 * LockRecord 实体测试
 */
class LockRecordTest extends TestCase
{
    private LockRecord $lockRecord;

    protected function setUp(): void
    {
        $this->lockRecord = new LockRecord();
    }

    public function test_construct_setsDefaultValues(): void
    {
        $record = new LockRecord();
        
        $this->assertNull($record->getUser());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getCreatedAt());
    }

    public function test_actionTypeEnum_hasCorrectValues(): void
    {
        $this->assertEquals('locked', ActionType::LOCKED->value);
        $this->assertEquals('unlocked', ActionType::UNLOCKED->value);
        $this->assertEquals('timeout', ActionType::TIMEOUT->value);
        $this->assertEquals('bypass_attempt', ActionType::BYPASS_ATTEMPT->value);
    }

    public function test_setUser_withValidUser(): void
    {
        $user = new TestUser(123);
        
        $result = $this->lockRecord->setUser($user);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertSame($user, $this->lockRecord->getUser());
        $this->assertEquals(123, $this->lockRecord->getUserId());
    }

    public function test_setUser_withNullValue(): void
    {
        // 先设置一个用户
        $this->lockRecord->setUser(new TestUser(456));
        
        $result = $this->lockRecord->setUser(null);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertNull($this->lockRecord->getUser());
        $this->assertNull($this->lockRecord->getUserId());
    }

    public function test_getUserId_withUserHavingGetIdMethod(): void
    {
        $user = new TestUser(123);
        $this->lockRecord->setUser($user);
        
        $result = $this->lockRecord->getUserId();
        
        $this->assertEquals(123, $result);
    }

    public function test_getUserId_withUserWithoutGetIdMethod(): void
    {
        $user = new class implements UserInterface {
            public function getUserIdentifier(): string
            {
                return '456';
            }
            
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }
            
            public function eraseCredentials(): void
            {
            }
        };
        
        $this->lockRecord->setUser($user);
        
        $result = $this->lockRecord->getUserId();
        
        $this->assertEquals(456, $result);
    }

    public function test_getUserId_withNonNumericIdentifier(): void
    {
        $user = new class implements UserInterface {
            public function getUserIdentifier(): string
            {
                return 'username';
            }
            
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }
            
            public function eraseCredentials(): void
            {
            }
        };
        
        $this->lockRecord->setUser($user);
        
        $result = $this->lockRecord->getUserId();
        
        $this->assertNull($result);
    }

    public function test_setUserId_isDeprecated(): void
    {
        // 设置用户
        $user = new TestUser(123);
        $this->lockRecord->setUser($user);
        
        // 调用废弃的方法应该不改变用户
        $result = $this->lockRecord->setUserId(456);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals(123, $this->lockRecord->getUserId());
    }

    public function test_setSessionId_withValidId(): void
    {
        $sessionId = 'sess_abc123def456';
        
        $result = $this->lockRecord->setSessionId($sessionId);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($sessionId, $this->lockRecord->getSessionId());
    }

    public function test_setSessionId_withEmptyString(): void
    {
        $result = $this->lockRecord->setSessionId('');
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals('', $this->lockRecord->getSessionId());
    }

    public function test_setSessionId_withLongId(): void
    {
        $longSessionId = str_repeat('a', 128);
        
        $result = $this->lockRecord->setSessionId($longSessionId);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($longSessionId, $this->lockRecord->getSessionId());
    }

    public function test_setActionType_withValidTypes(): void
    {
        $actionTypes = [
            ActionType::LOCKED,
            ActionType::UNLOCKED,
            ActionType::TIMEOUT,
            ActionType::BYPASS_ATTEMPT,
        ];
        
        foreach ($actionTypes as $actionType) {
            $result = $this->lockRecord->setActionType($actionType);
            
            $this->assertSame($this->lockRecord, $result);
            $this->assertSame($actionType, $this->lockRecord->getActionType());
        }
    }

    public function test_setRoute_withValidRoute(): void
    {
        $route = '/billing/invoice/123';
        
        $result = $this->lockRecord->setRoute($route);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($route, $this->lockRecord->getRoute());
    }

    public function test_setRoute_withSpecialCharacters(): void
    {
        $routes = [
            '/path/with/中文',
            '/path/with spaces',
            '/path/with-dashes_and_underscores',
            '/api/v1/users/{id}',
            '/search?q=test&sort=date',
        ];
        
        foreach ($routes as $route) {
            $this->lockRecord->setRoute($route);
            $this->assertEquals($route, $this->lockRecord->getRoute());
        }
    }

    public function test_setIpAddress_withValidIpv4(): void
    {
        $ipAddress = '192.168.1.100';
        
        $result = $this->lockRecord->setIpAddress($ipAddress);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($ipAddress, $this->lockRecord->getIpAddress());
    }

    public function test_setIpAddress_withValidIpv6(): void
    {
        $ipAddress = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        
        $result = $this->lockRecord->setIpAddress($ipAddress);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($ipAddress, $this->lockRecord->getIpAddress());
    }

    public function test_setIpAddress_withNullValue(): void
    {
        $result = $this->lockRecord->setIpAddress(null);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertNull($this->lockRecord->getIpAddress());
    }

    public function test_setUserAgent_withValidString(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        $result = $this->lockRecord->setUserAgent($userAgent);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($userAgent, $this->lockRecord->getUserAgent());
    }

    public function test_setUserAgent_withNullValue(): void
    {
        $result = $this->lockRecord->setUserAgent(null);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertNull($this->lockRecord->getUserAgent());
    }

    public function test_setContext_withValidArray(): void
    {
        $context = [
            'reason' => 'idle_timeout',
            'timeout_seconds' => 60,
            'metadata' => ['browser' => 'chrome', 'os' => 'windows'],
        ];
        
        $result = $this->lockRecord->setContext($context);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($context, $this->lockRecord->getContext());
    }

    public function test_setContext_withEmptyArray(): void
    {
        $result = $this->lockRecord->setContext([]);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals([], $this->lockRecord->getContext());
    }

    public function test_setContext_withNullValue(): void
    {
        $result = $this->lockRecord->setContext(null);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertNull($this->lockRecord->getContext());
    }

    public function test_setContext_withNestedArray(): void
    {
        $context = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value',
                ],
            ],
        ];
        
        $result = $this->lockRecord->setContext($context);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($context, $this->lockRecord->getContext());
    }

    public function test_isLockAction_returnsTrueForLockedAction(): void
    {
        $this->lockRecord->setActionType(ActionType::LOCKED);
        
        $this->assertTrue($this->lockRecord->isLockAction());
        $this->assertFalse($this->lockRecord->isUnlockAction());
        $this->assertFalse($this->lockRecord->isTimeoutAction());
        $this->assertFalse($this->lockRecord->isBypassAttempt());
    }

    public function test_isUnlockAction_returnsTrueForUnlockedAction(): void
    {
        $this->lockRecord->setActionType(ActionType::UNLOCKED);
        
        $this->assertFalse($this->lockRecord->isLockAction());
        $this->assertTrue($this->lockRecord->isUnlockAction());
        $this->assertFalse($this->lockRecord->isTimeoutAction());
        $this->assertFalse($this->lockRecord->isBypassAttempt());
    }

    public function test_isTimeoutAction_returnsTrueForTimeoutAction(): void
    {
        $this->lockRecord->setActionType(ActionType::TIMEOUT);
        
        $this->assertFalse($this->lockRecord->isLockAction());
        $this->assertFalse($this->lockRecord->isUnlockAction());
        $this->assertTrue($this->lockRecord->isTimeoutAction());
        $this->assertFalse($this->lockRecord->isBypassAttempt());
    }

    public function test_isBypassAttempt_returnsTrueForBypassAction(): void
    {
        $this->lockRecord->setActionType(ActionType::BYPASS_ATTEMPT);
        
        $this->assertFalse($this->lockRecord->isLockAction());
        $this->assertFalse($this->lockRecord->isUnlockAction());
        $this->assertFalse($this->lockRecord->isTimeoutAction());
        $this->assertTrue($this->lockRecord->isBypassAttempt());
    }

    public function test_getCreatedAt_remainsConstant(): void
    {
        $originalCreatedAt = $this->lockRecord->getCreatedAt();
        
        // 修改记录
        $this->lockRecord->setUser(new TestUser(123));
        $this->lockRecord->setActionType(ActionType::LOCKED);
        $this->lockRecord->setRoute('/test');
        
        $this->assertEquals($originalCreatedAt, $this->lockRecord->getCreatedAt());
    }

    /**
     * 测试方法链式调用
     */
    public function test_methodChaining(): void
    {
        $user = new TestUser(123);
        $result = $this->lockRecord
            ->setUser($user)
            ->setSessionId('sess_abc123')
            ->setActionType(ActionType::LOCKED)
            ->setRoute('/billing/invoice')
            ->setIpAddress('192.168.1.100')
            ->setUserAgent('Mozilla/5.0')
            ->setContext(['reason' => 'timeout']);
            
        $this->assertSame($this->lockRecord, $result);
        $this->assertSame($user, $this->lockRecord->getUser());
        $this->assertEquals(123, $this->lockRecord->getUserId());
        $this->assertEquals('sess_abc123', $this->lockRecord->getSessionId());
        $this->assertSame(ActionType::LOCKED, $this->lockRecord->getActionType());
        $this->assertEquals('/billing/invoice', $this->lockRecord->getRoute());
        $this->assertEquals('192.168.1.100', $this->lockRecord->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $this->lockRecord->getUserAgent());
        $this->assertEquals(['reason' => 'timeout'], $this->lockRecord->getContext());
    }

    /**
     * 测试边界值
     */
    public function test_extremeValues(): void
    {
        // 极长字符串
        $longString = str_repeat('x', 10000);
        $this->lockRecord->setRoute($longString);
        $this->assertEquals($longString, $this->lockRecord->getRoute());
        
        $this->lockRecord->setUserAgent($longString);
        $this->assertEquals($longString, $this->lockRecord->getUserAgent());
    }
}
