<?php

namespace Tourze\IdleLockScreenBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;

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
        
        $this->assertNull($record->getUserId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getCreatedAt());
    }

    public function test_actionTypeConstants_haveCorrectValues(): void
    {
        $this->assertEquals('locked', LockRecord::ACTION_LOCKED);
        $this->assertEquals('unlocked', LockRecord::ACTION_UNLOCKED);
        $this->assertEquals('timeout', LockRecord::ACTION_TIMEOUT);
        $this->assertEquals('bypass_attempt', LockRecord::ACTION_BYPASS_ATTEMPT);
    }

    public function test_setUserId_withValidId(): void
    {
        $userId = 123;
        
        $result = $this->lockRecord->setUserId($userId);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($userId, $this->lockRecord->getUserId());
    }

    public function test_setUserId_withNullValue(): void
    {
        // 先设置一个值
        $this->lockRecord->setUserId(456);
        
        $result = $this->lockRecord->setUserId(null);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertNull($this->lockRecord->getUserId());
    }

    public function test_setUserId_withZeroValue(): void
    {
        $result = $this->lockRecord->setUserId(0);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals(0, $this->lockRecord->getUserId());
    }

    public function test_setUserId_withNegativeValue(): void
    {
        $result = $this->lockRecord->setUserId(-1);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals(-1, $this->lockRecord->getUserId());
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
            LockRecord::ACTION_LOCKED,
            LockRecord::ACTION_UNLOCKED,
            LockRecord::ACTION_TIMEOUT,
            LockRecord::ACTION_BYPASS_ATTEMPT,
        ];
        
        foreach ($actionTypes as $actionType) {
            $result = $this->lockRecord->setActionType($actionType);
            
            $this->assertSame($this->lockRecord, $result);
            $this->assertEquals($actionType, $this->lockRecord->getActionType());
        }
    }

    public function test_setActionType_withCustomType(): void
    {
        $customType = 'custom_action';
        
        $result = $this->lockRecord->setActionType($customType);
        
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals($customType, $this->lockRecord->getActionType());
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
        $this->lockRecord->setActionType(LockRecord::ACTION_LOCKED);
        
        $this->assertTrue($this->lockRecord->isLockAction());
        $this->assertFalse($this->lockRecord->isUnlockAction());
        $this->assertFalse($this->lockRecord->isTimeoutAction());
        $this->assertFalse($this->lockRecord->isBypassAttempt());
    }

    public function test_isUnlockAction_returnsTrueForUnlockedAction(): void
    {
        $this->lockRecord->setActionType(LockRecord::ACTION_UNLOCKED);
        
        $this->assertFalse($this->lockRecord->isLockAction());
        $this->assertTrue($this->lockRecord->isUnlockAction());
        $this->assertFalse($this->lockRecord->isTimeoutAction());
        $this->assertFalse($this->lockRecord->isBypassAttempt());
    }

    public function test_isTimeoutAction_returnsTrueForTimeoutAction(): void
    {
        $this->lockRecord->setActionType(LockRecord::ACTION_TIMEOUT);
        
        $this->assertFalse($this->lockRecord->isLockAction());
        $this->assertFalse($this->lockRecord->isUnlockAction());
        $this->assertTrue($this->lockRecord->isTimeoutAction());
        $this->assertFalse($this->lockRecord->isBypassAttempt());
    }

    public function test_isBypassAttempt_returnsTrueForBypassAction(): void
    {
        $this->lockRecord->setActionType(LockRecord::ACTION_BYPASS_ATTEMPT);
        
        $this->assertFalse($this->lockRecord->isLockAction());
        $this->assertFalse($this->lockRecord->isUnlockAction());
        $this->assertFalse($this->lockRecord->isTimeoutAction());
        $this->assertTrue($this->lockRecord->isBypassAttempt());
    }

    public function test_statusMethods_returnFalseForCustomAction(): void
    {
        $this->lockRecord->setActionType('custom_action');
        
        $this->assertFalse($this->lockRecord->isLockAction());
        $this->assertFalse($this->lockRecord->isUnlockAction());
        $this->assertFalse($this->lockRecord->isTimeoutAction());
        $this->assertFalse($this->lockRecord->isBypassAttempt());
    }

    public function test_getCreatedAt_remainsConstant(): void
    {
        $originalCreatedAt = $this->lockRecord->getCreatedAt();
        
        // 修改记录
        $this->lockRecord->setUserId(123);
        $this->lockRecord->setActionType(LockRecord::ACTION_LOCKED);
        $this->lockRecord->setRoute('/test');
        
        $this->assertEquals($originalCreatedAt, $this->lockRecord->getCreatedAt());
    }

    /**
     * 测试方法链式调用
     */
    public function test_methodChaining(): void
    {
        $result = $this->lockRecord
            ->setUserId(123)
            ->setSessionId('sess_abc123')
            ->setActionType(LockRecord::ACTION_LOCKED)
            ->setRoute('/billing/invoice')
            ->setIpAddress('192.168.1.100')
            ->setUserAgent('Mozilla/5.0')
            ->setContext(['reason' => 'timeout']);
            
        $this->assertSame($this->lockRecord, $result);
        $this->assertEquals(123, $this->lockRecord->getUserId());
        $this->assertEquals('sess_abc123', $this->lockRecord->getSessionId());
        $this->assertEquals(LockRecord::ACTION_LOCKED, $this->lockRecord->getActionType());
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
        // 极端用户ID
        $this->lockRecord->setUserId(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $this->lockRecord->getUserId());
        
        $this->lockRecord->setUserId(PHP_INT_MIN);
        $this->assertEquals(PHP_INT_MIN, $this->lockRecord->getUserId());
        
        // 极长字符串
        $longString = str_repeat('x', 10000);
        $this->lockRecord->setRoute($longString);
        $this->assertEquals($longString, $this->lockRecord->getRoute());
        
        $this->lockRecord->setUserAgent($longString);
        $this->assertEquals($longString, $this->lockRecord->getUserAgent());
    }
} 