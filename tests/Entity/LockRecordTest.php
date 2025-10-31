<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Enum\ActionType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(LockRecord::class)]
final class LockRecordTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LockRecord();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'sessionId' => ['sessionId', 'test-session-123'];
        yield 'actionType' => ['actionType', ActionType::LOCKED];
        yield 'route' => ['route', '/test/route'];
        yield 'ipAddress' => ['ipAddress', '192.168.1.100'];
        yield 'userAgent' => ['userAgent', 'Mozilla/5.0 Test Browser'];
        yield 'context' => ['context', ['key' => 'value', 'number' => 42]];
        yield 'user' => ['user', null]; // We'll handle stub creation in the test method
    }

    public function testCanBeInstantiated(): void
    {
        $record = new LockRecord();

        $this->assertInstanceOf(LockRecord::class, $record);
        $this->assertNull($record->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getCreateTime());
    }

    public function testNullableFields(): void
    {
        $record = new LockRecord();

        $this->assertNull($record->getUser());
        $this->assertNull($record->getIpAddress());
        $this->assertNull($record->getUserAgent());
        $this->assertNull($record->getContext());

        $record->setUser(null);
        $record->setIpAddress(null);
        $record->setUserAgent(null);
        $record->setContext(null);

        $this->assertNull($record->getUser());
        $this->assertNull($record->getIpAddress());
        $this->assertNull($record->getUserAgent());
        $this->assertNull($record->getContext());
    }

    public function testActionTypeCheckers(): void
    {
        $record = new LockRecord();

        $record->setActionType(ActionType::LOCKED);
        $this->assertTrue($record->isLockAction());
        $this->assertFalse($record->isUnlockAction());
        $this->assertFalse($record->isTimeoutAction());
        $this->assertFalse($record->isBypassAttempt());

        $record->setActionType(ActionType::UNLOCKED);
        $this->assertFalse($record->isLockAction());
        $this->assertTrue($record->isUnlockAction());
        $this->assertFalse($record->isTimeoutAction());
        $this->assertFalse($record->isBypassAttempt());

        $record->setActionType(ActionType::TIMEOUT);
        $this->assertFalse($record->isLockAction());
        $this->assertFalse($record->isUnlockAction());
        $this->assertTrue($record->isTimeoutAction());
        $this->assertFalse($record->isBypassAttempt());

        $record->setActionType(ActionType::BYPASS_ATTEMPT);
        $this->assertFalse($record->isLockAction());
        $this->assertFalse($record->isUnlockAction());
        $this->assertFalse($record->isTimeoutAction());
        $this->assertTrue($record->isBypassAttempt());
    }

    public function testToString(): void
    {
        $record = new LockRecord();
        $record->setActionType(ActionType::LOCKED);
        $record->setRoute('/test/route');

        $toString = $record->__toString();

        $this->assertStringContainsString('locked', $toString);
        $this->assertStringContainsString('/test/route', $toString);
        $this->assertStringContainsString($record->getCreateTime()->format('Y-m-d H:i:s'), $toString);
    }

    public function testFluentInterface(): void
    {
        $record = new LockRecord();
        $user = $this->createMock(UserInterface::class);

        $record->setUser($user);
        $record->setSessionId('test-session');
        $record->setActionType(ActionType::LOCKED);
        $record->setRoute('/test');
        $record->setIpAddress('127.0.0.1');
        $record->setUserAgent('Test Agent');
        $record->setContext(['test' => true]);

        $this->assertSame($user, $record->getUser());
        $this->assertSame('test-session', $record->getSessionId());
        $this->assertSame(ActionType::LOCKED, $record->getActionType());
        $this->assertSame('/test', $record->getRoute());
        $this->assertSame('127.0.0.1', $record->getIpAddress());
        $this->assertSame('Test Agent', $record->getUserAgent());
        $this->assertSame(['test' => true], $record->getContext());
    }
}
