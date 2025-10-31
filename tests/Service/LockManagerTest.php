<?php

namespace Tourze\IdleLockScreenBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Enum\ActionType;
use Tourze\IdleLockScreenBundle\Service\LockManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * LockManager 集成测试用例
 *
 * @internal
 */
#[CoversClass(LockManager::class)]
#[RunTestsInSeparateProcesses]
final class LockManagerTest extends AbstractIntegrationTestCase
{
    private ?LockManager $lockManager = null;

    protected function onSetUp(): void
    {
        // 集成测试设置
    }

    public function getLockManager(): LockManager
    {
        if (null === $this->lockManager) {
            $lockManager = self::getContainer()->get(LockManager::class);
            $this->assertInstanceOf(LockManager::class, $lockManager);
            $this->lockManager = $lockManager;
        }

        return $this->lockManager;
    }

    /**
     * 测试锁定会话 - 正常情况
     */
    public function testLockSessionWithValidSession(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        $route = '/billing/invoice';
        $reason = 'idle_timeout';

        // 创建带会话的请求
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        // 模拟用户登录
        $this->setAuthenticatedUser($user);

        // 执行锁定操作
        $this->getLockManager()->lockSession($route, $reason);

        // 验证会话状态
        $this->assertTrue($session->get('_idle_lock_status', false));
        $this->assertEquals($route, $session->get('_idle_lock_route'));
        $this->assertIsInt($session->get('_idle_lock_time'));

        // 验证数据库记录
        $lockRecords = self::getEntityManager()
            ->getRepository(LockRecord::class)
            ->findBy(['user' => $user])
        ;

        $this->assertCount(1, $lockRecords);
        $lockRecord = $lockRecords[0];
        $this->assertEquals(ActionType::LOCKED, $lockRecord->getActionType());
        $this->assertEquals($route, $lockRecord->getRoute());
    }

    /**
     * 测试锁定会话 - 没有会话
     */
    public function testLockSessionWithoutSession(): void
    {
        // 清理所有现有记录
        /** @var EntityManagerInterface $em */
        $em = self::getEntityManager();
        $em
            ->createQuery('DELETE FROM ' . LockRecord::class)
            ->execute()
        ;

        // 确保没有当前请求
        $requestStack = self::getService(RequestStack::class);
        while (null !== $requestStack->getCurrentRequest()) {
            $requestStack->pop();
        }

        // 执行锁定操作，应该无异常
        $this->getLockManager()->lockSession('/test');

        // 验证没有创建锁定记录
        $lockRecords = self::getEntityManager()
            ->getRepository(LockRecord::class)
            ->findAll()
        ;

        $this->assertCount(0, $lockRecords);
    }

    /**
     * 测试解锁会话 - 正常情况
     */
    public function testUnlockSessionWithValidSession(): void
    {
        $user = $this->createNormalUser('unlock@example.com', 'password');
        $route = '/billing/invoice';

        // 创建带会话的请求
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);
        $this->setAuthenticatedUser($user);

        // 先锁定会话
        $this->getLockManager()->lockSession($route);

        // 验证已锁定
        $this->assertTrue($session->get('_idle_lock_status', false));

        // 执行解锁操作
        $this->getLockManager()->unlockSession();

        // 验证会话状态已清除
        $this->assertFalse($session->get('_idle_lock_status', false));
        $this->assertNull($session->get('_idle_lock_route'));
        $this->assertNull($session->get('_idle_lock_time'));

        // 验证数据库记录（按创建时间排序确保顺序一致）
        $lockRecords = self::getEntityManager()
            ->getRepository(LockRecord::class)
            ->findBy(['user' => $user], ['id' => 'ASC'])
        ;

        $this->assertCount(2, $lockRecords); // 一个LOCKED，一个UNLOCKED
        $unlockRecord = end($lockRecords);
        $this->assertInstanceOf(LockRecord::class, $unlockRecord, 'Expected last record to be LockRecord instance');
        $this->assertEquals(ActionType::UNLOCKED, $unlockRecord->getActionType());
    }

    /**
     * 测试解锁会话 - 没有会话
     */
    public function testUnlockSessionWithoutSession(): void
    {
        // 清理所有现有记录
        /** @var EntityManagerInterface $em */
        $em = self::getEntityManager();
        $em
            ->createQuery('DELETE FROM ' . LockRecord::class)
            ->execute()
        ;

        // 确保没有当前请求
        $requestStack = self::getService(RequestStack::class);
        while (null !== $requestStack->getCurrentRequest()) {
            $requestStack->pop();
        }

        // 执行解锁操作，应该无异常
        $this->getLockManager()->unlockSession();

        // 验证没有创建记录
        $lockRecords = self::getEntityManager()
            ->getRepository(LockRecord::class)
            ->findAll()
        ;

        $this->assertCount(0, $lockRecords);
    }

    /**
     * 测试检查会话是否锁定 - 已锁定
     */
    public function testIsSessionLockedWithLockedSession(): void
    {
        $user = $this->createNormalUser('locked@example.com', 'password');

        // 创建带会话的请求
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);
        $this->setAuthenticatedUser($user);

        // 手动设置锁定状态
        $session->set('_idle_lock_status', true);

        $this->assertTrue($this->getLockManager()->isSessionLocked());
    }

    /**
     * 测试检查会话是否锁定 - 未锁定
     */
    public function testIsSessionLockedWithUnlockedSession(): void
    {
        // 创建带会话的请求
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $this->assertFalse($this->getLockManager()->isSessionLocked());
    }

    /**
     * 测试检查会话是否锁定 - 没有会话
     */
    public function testIsSessionLockedWithoutSession(): void
    {
        // 确保没有当前请求
        $requestStack = self::getService(RequestStack::class);
        while (null !== $requestStack->getCurrentRequest()) {
            $requestStack->pop();
        }

        $this->assertFalse($this->getLockManager()->isSessionLocked());
    }

    /**
     * 测试获取锁定路由
     */
    public function testGetLockedRoute(): void
    {
        $lockedRoute = '/billing/invoice';

        // 创建带会话的请求
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        // 设置锁定路由
        $session->set('_idle_lock_route', $lockedRoute);

        $this->assertEquals($lockedRoute, $this->getLockManager()->getLockedRoute());
    }

    /**
     * 测试获取锁定路由 - 没有会话
     */
    public function testGetLockedRouteWithoutSession(): void
    {
        // 确保没有当前请求
        $requestStack = self::getService(RequestStack::class);
        while (null !== $requestStack->getCurrentRequest()) {
            $requestStack->pop();
        }

        $this->assertNull($this->getLockManager()->getLockedRoute());
    }

    /**
     * 测试获取锁定时间
     */
    public function testGetLockTime(): void
    {
        $lockTime = time();

        // 创建带会话的请求
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        // 设置锁定时间
        $session->set('_idle_lock_time', $lockTime);

        $this->assertEquals($lockTime, $this->getLockManager()->getLockTime());
    }

    /**
     * 测试获取锁定时间 - 没有会话
     */
    public function testGetLockTimeWithoutSession(): void
    {
        // 确保没有当前请求
        $requestStack = self::getService(RequestStack::class);
        while (null !== $requestStack->getCurrentRequest()) {
            $requestStack->pop();
        }

        $this->assertNull($this->getLockManager()->getLockTime());
    }

    /**
     * 测试清除过期锁定 - 用户已登录且有锁定
     */
    public function testClearExpiredLocksWithLoggedInUserAndLock(): void
    {
        $user = $this->createNormalUser('expired@example.com', 'password');

        // 创建带会话的请求
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);
        $this->setAuthenticatedUser($user);

        // 设置锁定状态
        $session->set('_idle_lock_status', true);
        $session->set('_idle_lock_route', '/locked/route');

        // 执行清除过期锁定
        $this->getLockManager()->clearExpiredLocks();

        // 验证锁定状态已清除
        $this->assertFalse($session->get('_idle_lock_status', false));

        // 验证数据库记录
        $lockRecords = self::getEntityManager()
            ->getRepository(LockRecord::class)
            ->findBy(['user' => $user])
        ;

        $this->assertCount(1, $lockRecords);
        $this->assertEquals(ActionType::UNLOCKED, $lockRecords[0]->getActionType());
    }

    /**
     * 测试获取用户锁定历史
     */
    public function testGetUserLockHistory(): void
    {
        // 清理所有现有记录
        /** @var EntityManagerInterface $em */
        $em = self::getEntityManager();
        $em
            ->createQuery('DELETE FROM ' . LockRecord::class)
            ->execute()
        ;

        $user = $this->createNormalUser('history@example.com', 'password');
        $this->setAuthenticatedUser($user);

        // 创建锁定记录
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $this->getLockManager()->lockSession('/test/route');
        // 添加延迟确保时间戳不同
        usleep(1000);
        $this->getLockManager()->unlockSession();

        // 获取历史记录
        $history = $this->getLockManager()->getUserLockHistory();

        $this->assertCount(2, $history);
        $this->assertEquals(ActionType::UNLOCKED, $history[0]->getActionType()); // 按时间倒序
        $this->assertEquals(ActionType::LOCKED, $history[1]->getActionType());
    }

    /**
     * 测试获取会话锁定历史
     */
    public function testGetSessionLockHistory(): void
    {
        $user = $this->createNormalUser('session@example.com', 'password');

        // 创建带会话的请求
        $request = Request::create('/test');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);
        $this->setAuthenticatedUser($user);

        // 创建锁定记录
        $this->getLockManager()->lockSession('/session/route');

        // 获取会话历史记录
        $history = $this->getLockManager()->getSessionLockHistory();

        $this->assertCount(1, $history);
        $this->assertEquals(ActionType::LOCKED, $history[0]->getActionType());
    }

    /**
     * 测试记录超时
     */
    public function testRecordTimeout(): void
    {
        $user = $this->createNormalUser('timeout@example.com', 'password');
        $route = '/admin/dashboard';

        // 创建带会话的请求
        $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'Test Browser']);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);
        $this->setAuthenticatedUser($user);

        // 记录超时
        $this->getLockManager()->recordTimeout($route);

        // 验证记录
        $lockRecords = self::getEntityManager()
            ->getRepository(LockRecord::class)
            ->findBy(['user' => $user])
        ;

        $this->assertCount(1, $lockRecords);
        $this->assertEquals(ActionType::TIMEOUT, $lockRecords[0]->getActionType());
        $this->assertEquals($route, $lockRecords[0]->getRoute());
    }

    /**
     * 测试记录绕过尝试
     */
    public function testRecordBypassAttempt(): void
    {
        $user = $this->createNormalUser('bypass@example.com', 'password');
        $route = '/admin/settings';
        $method = 'POST';

        // 创建带会话的请求
        $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'Test Browser']);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);
        $this->setAuthenticatedUser($user);

        // 记录绕过尝试
        $this->getLockManager()->recordBypassAttempt($route, $method);

        // 验证记录
        $lockRecords = self::getEntityManager()
            ->getRepository(LockRecord::class)
            ->findBy(['user' => $user])
        ;

        $this->assertCount(1, $lockRecords);
        $this->assertEquals(ActionType::BYPASS_ATTEMPT, $lockRecords[0]->getActionType());
        $this->assertEquals($route, $lockRecords[0]->getRoute());
    }
}
