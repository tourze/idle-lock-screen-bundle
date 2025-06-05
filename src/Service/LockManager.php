<?php

namespace Tourze\IdleLockScreenBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Enum\ActionType;

/**
 * 锁定管理服务
 * 负责管理用户的锁定状态和记录锁定操作
 */
class LockManager
{
    private const SESSION_LOCK_KEY = '_idle_lock_status';
    private const SESSION_LOCK_ROUTE_KEY = '_idle_lock_route';
    private const SESSION_LOCK_TIME_KEY = '_idle_lock_time';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly Security $security
    ) {
    }

    /**
     * 锁定用户会话
     */
    public function lockSession(string $route, ?string $reason = null): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $session->set(self::SESSION_LOCK_KEY, true);
        $session->set(self::SESSION_LOCK_ROUTE_KEY, $route);
        $session->set(self::SESSION_LOCK_TIME_KEY, time());

        $this->recordLockAction(ActionType::LOCKED, $route, [
            'reason' => $reason,
            'lock_time' => time()
        ]);
    }

    /**
     * 解锁用户会话
     */
    public function unlockSession(?string $route = null): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $lockedRoute = $session->get(self::SESSION_LOCK_ROUTE_KEY);

        $session->remove(self::SESSION_LOCK_KEY);
        $session->remove(self::SESSION_LOCK_ROUTE_KEY);
        $session->remove(self::SESSION_LOCK_TIME_KEY);

        $this->recordLockAction(ActionType::UNLOCKED, $route ?? $lockedRoute ?? 'unknown');
    }

    /**
     * 检查会话是否被锁定
     */
    public function isSessionLocked(): bool
    {
        $session = $this->getSession();
        if (!$session) {
            return false;
        }

        return (bool) $session->get(self::SESSION_LOCK_KEY, false);
    }

    /**
     * 获取锁定的路由
     */
    public function getLockedRoute(): ?string
    {
        $session = $this->getSession();
        if (!$session) {
            return null;
        }

        return $session->get(self::SESSION_LOCK_ROUTE_KEY);
    }

    /**
     * 获取锁定时间
     */
    public function getLockTime(): ?int
    {
        $session = $this->getSession();
        if (!$session) {
            return null;
        }

        return $session->get(self::SESSION_LOCK_TIME_KEY);
    }

    /**
     * 记录超时事件
     */
    public function recordTimeout(string $route): void
    {
        $this->recordLockAction(ActionType::TIMEOUT, $route, [
            'timeout_time' => time()
        ]);
    }

    /**
     * 记录绕过尝试
     */
    public function recordBypassAttempt(string $route, ?string $method = null): void
    {
        $this->recordLockAction(ActionType::BYPASS_ATTEMPT, $route, [
            'attempt_time' => time(),
            'method' => $method
        ]);
    }

    /**
     * 清除过期的锁定状态
     * 当用户重新登录时调用
     */
    public function clearExpiredLocks(): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        // 检查是否有新的用户登录
        $user = $this->security->getUser();
        if ($user && $this->isSessionLocked()) {
            // 如果用户重新登录，清除锁定状态
            $this->unlockSession();
        }
    }

    /**
     * 获取用户的锁定历史记录
     */
    public function getUserLockHistory(?int $userId = null, int $limit = 50): array
    {
        $currentUser = $this->security->getUser();

        // 如果没有指定用户ID且当前也没有登录用户，返回空数组
        if ($userId === null && $currentUser === null) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('lr')
            ->from(LockRecord::class, 'lr')
            ->orderBy('lr.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($userId !== null && $currentUser !== null) {
            // 指定了用户ID，查询该用户的记录（包括关联和兼容旧数据）
            $qb->where('lr.user = :user OR (lr.user IS NULL AND lr.userId = :userId)')
                ->setParameter('user', $currentUser)
                ->setParameter('userId', $userId);
        } elseif ($currentUser !== null) {
            // 没有指定用户ID，查询当前登录用户的记录
            $qb->where('lr.user = :user')
                ->setParameter('user', $currentUser);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 获取会话的锁定历史记录
     */
    public function getSessionLockHistory(?string $sessionId = null, int $limit = 20): array
    {
        $session = $sessionId ?? $this->getCurrentSessionId();
        if (!$session) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('lr')
            ->from(LockRecord::class, 'lr')
            ->where('lr.sessionId = :sessionId')
            ->setParameter('sessionId', $session)
            ->orderBy('lr.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * 记录锁定操作
     */
    private function recordLockAction(ActionType $actionType, string $route, ?array $context = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $record = new LockRecord();
        $record->setUser($this->security->getUser())
            ->setSessionId($this->getCurrentSessionId() ?? 'unknown')
            ->setActionType($actionType)
            ->setRoute($route)
            ->setIpAddress($request->getClientIp())
            ->setUserAgent($request->headers->get('User-Agent'))
            ->setContext($context);

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    /**
     * 获取当前会话
     */
    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getSession();
    }

    /**
     * 获取当前会话ID
     */
    private function getCurrentSessionId(): ?string
    {
        $session = $this->getSession();
        return $session?->getId();
    }
}
