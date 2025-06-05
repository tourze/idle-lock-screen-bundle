<?php

namespace Tourze\IdleLockScreenBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Service\LockManager;

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
 * LockManager 测试用例
 */
class LockManagerTest extends TestCase
{
    private LockManager $lockManager;
    private EntityManagerInterface&MockObject $entityManager;
    private RequestStack&MockObject $requestStack;
    private Security&MockObject $security;
    private Request&MockObject $request;
    private SessionInterface&MockObject $session;
    private TestUser $user;
    private QueryBuilder&MockObject $queryBuilder;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->security = $this->createMock(Security::class);
        $this->request = $this->createMock(Request::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->user = new TestUser(123);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->lockManager = new LockManager(
            $this->entityManager,
            $this->requestStack,
            $this->security
        );
    }

    /**
     * 测试锁定会话 - 正常情况
     */
    public function test_lockSession_withValidSession(): void
    {
        $route = '/billing/invoice';
        $reason = 'idle_timeout';
        
        $this->setupRequestAndSession();
        $this->setupUserMocks(123, 'sess_abc123');
        
        $this->session
            ->expects($this->exactly(3))
            ->method('set')
            ->with($this->logicalOr(
                $this->equalTo('_idle_lock_status'),
                $this->equalTo('_idle_lock_route'),
                $this->equalTo('_idle_lock_time')
            ));

        $this->setupRecordPersistence();

        $this->lockManager->lockSession($route, $reason);
    }

    /**
     * 测试锁定会话 - 没有会话
     */
    public function test_lockSession_withoutSession(): void
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        // 应该没有任何操作
        $this->session
            ->expects($this->never())
            ->method('set');

        $this->lockManager->lockSession('/test');
    }

    /**
     * 测试解锁会话 - 正常情况
     */
    public function test_unlockSession_withValidSession(): void
    {
        $lockedRoute = '/billing/invoice';
        
        $this->setupRequestAndSession();
        $this->setupUserMocks(123, 'sess_abc123');
        
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('_idle_lock_route')
            ->willReturn($lockedRoute);

        $this->session
            ->expects($this->exactly(3))
            ->method('remove')
            ->with($this->logicalOr(
                $this->equalTo('_idle_lock_status'),
                $this->equalTo('_idle_lock_route'),
                $this->equalTo('_idle_lock_time')
            ));

        $this->setupRecordPersistence();

        $this->lockManager->unlockSession();
    }

    /**
     * 测试解锁会话 - 指定路由
     */
    public function test_unlockSession_withSpecifiedRoute(): void
    {
        $specifiedRoute = '/custom/route';
        
        $this->setupRequestAndSession();
        $this->setupUserMocks(123, 'sess_abc123');
        
        $this->session
            ->expects($this->exactly(3))
            ->method('remove');

        $this->setupRecordPersistence();

        $this->lockManager->unlockSession($specifiedRoute);
    }

    /**
     * 测试解锁会话 - 没有会话
     */
    public function test_unlockSession_withoutSession(): void
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        // 应该没有任何操作
        $this->session
            ->expects($this->never())
            ->method('remove');

        $this->lockManager->unlockSession();
    }

    /**
     * 测试检查会话是否锁定 - 已锁定
     */
    public function test_isSessionLocked_withLockedSession(): void
    {
        $this->setupRequestAndSession();
        
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('_idle_lock_status', false)
            ->willReturn(true);

        $result = $this->lockManager->isSessionLocked();
        
        $this->assertTrue($result);
    }

    /**
     * 测试检查会话是否锁定 - 未锁定
     */
    public function test_isSessionLocked_withUnlockedSession(): void
    {
        $this->setupRequestAndSession();
        
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('_idle_lock_status', false)
            ->willReturn(false);

        $result = $this->lockManager->isSessionLocked();
        
        $this->assertFalse($result);
    }

    /**
     * 测试检查会话是否锁定 - 没有会话
     */
    public function test_isSessionLocked_withoutSession(): void
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $result = $this->lockManager->isSessionLocked();
        
        $this->assertFalse($result);
    }

    /**
     * 测试获取锁定路由
     */
    public function test_getLockedRoute_withLockedSession(): void
    {
        $lockedRoute = '/billing/invoice';
        
        $this->setupRequestAndSession();
        
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('_idle_lock_route')
            ->willReturn($lockedRoute);

        $result = $this->lockManager->getLockedRoute();
        
        $this->assertEquals($lockedRoute, $result);
    }

    /**
     * 测试获取锁定路由 - 没有会话
     */
    public function test_getLockedRoute_withoutSession(): void
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $result = $this->lockManager->getLockedRoute();
        
        $this->assertNull($result);
    }

    /**
     * 测试获取锁定时间
     */
    public function test_getLockTime_withLockedSession(): void
    {
        $lockTime = time();
        
        $this->setupRequestAndSession();
        
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('_idle_lock_time')
            ->willReturn($lockTime);

        $result = $this->lockManager->getLockTime();
        
        $this->assertEquals($lockTime, $result);
    }

    /**
     * 测试获取锁定时间 - 没有会话
     */
    public function test_getLockTime_withoutSession(): void
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $result = $this->lockManager->getLockTime();
        
        $this->assertNull($result);
    }

    /**
     * 测试记录超时事件
     */
    public function test_recordTimeout(): void
    {
        $route = '/timeout/route';
        
        $this->setupRequestAndSession();
        $this->setupUserMocks(123, 'sess_abc123');
        $this->setupRecordPersistence();

        $this->lockManager->recordTimeout($route);
    }

    /**
     * 测试记录绕过尝试
     */
    public function test_recordBypassAttempt(): void
    {
        $route = '/bypass/route';
        $method = 'direct_access';
        
        $this->setupRequestAndSession();
        $this->setupUserMocks(123, 'sess_abc123');
        $this->setupRecordPersistence();

        $this->lockManager->recordBypassAttempt($route, $method);
    }

    /**
     * 测试记录绕过尝试 - 没有方法
     */
    public function test_recordBypassAttempt_withoutMethod(): void
    {
        $route = '/bypass/route';
        
        $this->setupRequestAndSession();
        $this->setupUserMocks(123, 'sess_abc123');
        $this->setupRecordPersistence();

        $this->lockManager->recordBypassAttempt($route);
    }

    /**
     * 测试清除过期锁定 - 用户已登录且有锁定
     */
    public function test_clearExpiredLocks_withLoggedInUserAndLock(): void
    {
        $this->setupRequestAndSession();
        
        $this->security
            ->expects($this->atLeast(1))
            ->method('getUser')
            ->willReturn($this->user);

        $this->session
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['_idle_lock_status', false, true],
                ['_idle_lock_route', null, '/locked/route']
            ]);

        $this->session
            ->expects($this->exactly(3))
            ->method('remove');

        $this->setupRecordPersistence();

        $this->lockManager->clearExpiredLocks();
    }

    /**
     * 测试清除过期锁定 - 没有用户登录
     */
    public function test_clearExpiredLocks_withoutLoggedInUser(): void
    {
        $this->setupRequestAndSession();
        
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        // 不应该尝试获取锁定状态
        $this->session
            ->expects($this->never())
            ->method('get');

        $this->lockManager->clearExpiredLocks();
    }

    /**
     * 测试清除过期锁定 - 用户已登录但没有锁定
     */
    public function test_clearExpiredLocks_withLoggedInUserButNoLock(): void
    {
        $this->setupRequestAndSession();
        
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('_idle_lock_status', false)
            ->willReturn(false);

        // 不应该尝试解锁
        $this->session
            ->expects($this->never())
            ->method('remove');

        $this->lockManager->clearExpiredLocks();
    }

    /**
     * 测试获取用户锁定历史 - 指定用户ID
     */
    public function test_getUserLockHistory_withSpecifiedUserId(): void
    {
        $userId = 456;
        $limit = 25;
        $mockRecords = [$this->createMockLockRecord()];

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->setupQueryBuilderForUserHistoryWithUserId($userId, $limit, $mockRecords);

        $result = $this->lockManager->getUserLockHistory($userId, $limit);

        $this->assertEquals($mockRecords, $result);
    }

    /**
     * 测试获取用户锁定历史 - 当前用户
     */
    public function test_getUserLockHistory_withCurrentUser(): void
    {
        $mockRecords = [$this->createMockLockRecord()];

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);
        
        $this->setupQueryBuilderForCurrentUserHistory(50, $mockRecords);

        $result = $this->lockManager->getUserLockHistory();
        
        $this->assertEquals($mockRecords, $result);
    }

    /**
     * 测试获取用户锁定历史 - 没有用户
     */
    public function test_getUserLockHistory_withoutUser(): void
    {
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->lockManager->getUserLockHistory();
        
        $this->assertEquals([], $result);
    }

    /**
     * 测试获取会话锁定历史 - 指定会话ID
     */
    public function test_getSessionLockHistory_withSpecifiedSessionId(): void
    {
        $sessionId = 'sess_custom123';
        $limit = 10;
        $mockRecords = [$this->createMockLockRecord()];

        $this->setupQueryBuilderForSessionHistory($sessionId, $limit, $mockRecords);

        $result = $this->lockManager->getSessionLockHistory($sessionId, $limit);
        
        $this->assertEquals($mockRecords, $result);
    }

    /**
     * 测试获取会话锁定历史 - 当前会话
     */
    public function test_getSessionLockHistory_withCurrentSession(): void
    {
        $sessionId = 'sess_abc123';
        $mockRecords = [$this->createMockLockRecord()];

        $this->setupRequestAndSession();
        $this->session
            ->expects($this->once())
            ->method('getId')
            ->willReturn($sessionId);

        $this->setupQueryBuilderForSessionHistory($sessionId, 20, $mockRecords);

        $result = $this->lockManager->getSessionLockHistory();
        
        $this->assertEquals($mockRecords, $result);
    }

    /**
     * 测试获取会话锁定历史 - 没有会话
     */
    public function test_getSessionLockHistory_withoutSession(): void
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $result = $this->lockManager->getSessionLockHistory();
        
        $this->assertEquals([], $result);
    }

    /**
     * 设置请求和会话 Mock
     */
    private function setupRequestAndSession(): void
    {
        $this->requestStack
            ->expects($this->atLeastOnce())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getSession')
            ->willReturn($this->session);
    }

    /**
     * 设置用户相关 Mock
     */
    private function setupUserMocks(int $userId, string $sessionId): void
    {
        // 设置用户ID（TestUser已经在构造函数中设置了）
        if ($this->user->getId() !== $userId) {
            $this->user = new TestUser($userId);
        }
        
        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($this->user);

        $this->session
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($sessionId);
    }

    /**
     * 设置记录持久化 Mock
     */
    private function setupRecordPersistence(): void
    {
        $headerBag = $this->createMock(HeaderBag::class);
        $this->request->headers = $headerBag;
        
        $this->request
            ->expects($this->once())
            ->method('getClientIp')
            ->willReturn('192.168.1.100');

        $headerBag
            ->expects($this->once())
            ->method('get')
            ->with('User-Agent')
            ->willReturn('Mozilla/5.0');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(LockRecord::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');
    }

    /**
     * 设置用户历史查询 Mock
     */
    private function setupQueryBuilderForUserHistory(int $userId, int $limit, array $records): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('lr')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with(LockRecord::class, 'lr')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('lr.user = :user OR (lr.user IS NULL AND lr.userId = :userId)')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function ($param, $value) use ($userId) {
                if ($param === 'user') {
                    $this->assertSame($this->user, $value);
                } elseif ($param === 'userId') {
                    $this->assertEquals($userId, $value);
                }
                return $this->queryBuilder;
            });

        $this->queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('lr.createdAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($records);
    }

    /**
     * 设置指定用户ID的历史查询 Mock
     */
    private function setupQueryBuilderForUserHistoryWithUserId(int $userId, int $limit, array $records): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('lr')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with(LockRecord::class, 'lr')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('lr.createdAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('lr.user = :user OR (lr.user IS NULL AND lr.userId = :userId)')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function ($param, $value) use ($userId) {
                if ($param === 'user') {
                    $this->assertSame($this->user, $value);
                } elseif ($param === 'userId') {
                    $this->assertEquals($userId, $value);
                }
                return $this->queryBuilder;
            });

        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($records);
    }

    /**
     * 设置当前用户历史查询 Mock
     */
    private function setupQueryBuilderForCurrentUserHistory(int $limit, array $records): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('lr')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with(LockRecord::class, 'lr')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('lr.createdAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('lr.user = :user')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('user', $this->user)
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($records);
    }

    /**
     * 设置会话历史查询 Mock
     */
    private function setupQueryBuilderForSessionHistory(string $sessionId, int $limit, array $records): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('lr')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with(LockRecord::class, 'lr')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('lr.sessionId = :sessionId')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('sessionId', $sessionId)
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('lr.createdAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($records);
    }

    /**
     * 创建真实的 LockRecord 对象用于测试
     */
    private function createMockLockRecord(): LockRecord
    {
        $record = new LockRecord();
        $record->setUser($this->user)
               ->setSessionId('test_session')
               ->setActionType(\Tourze\IdleLockScreenBundle\Enum\ActionType::LOCKED)
               ->setRoute('/test/route');
        
        return $record;
    }
} 