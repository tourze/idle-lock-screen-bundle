<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;
use Tourze\IdleLockScreenBundle\Service\LockManager;

final class IdleLockScreenIndexController extends AbstractController
{
    public function __construct(
        private readonly LockManager $lockManager,
        private readonly IdleLockDetector $lockDetector,
    ) {
    }

    #[Route(path: '/idle-lock/timeout', name: 'idle_lock_lock_screen', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        // POST 请求：处理 JavaScript 发送的锁定请求
        if ($request->isMethod('POST')) {
            return $this->handleTimeoutRequest($request);
        }

        // GET 请求：显示锁定页面
        return $this->showLockScreen($request);
    }

    private function handleTimeoutRequest(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = [];
        }
        $route = is_string($data['route'] ?? null) ? $data['route'] : $request->getPathInfo();

        // 检查路由是否需要锁定
        if (!$this->lockDetector->shouldLockRoute($route)) {
            return new JsonResponse(['error' => 'Route not configured for locking'], 400);
        }

        // 记录超时事件
        $this->lockManager->recordTimeout($route);

        // 锁定会话
        $this->lockManager->lockSession($route, 'idle_timeout');

        return new JsonResponse(['success' => true]);
    }

    private function showLockScreen(Request $request): Response
    {
        // 如果没有被锁定，检查是否应该被锁定
        if (!$this->lockManager->isSessionLocked()) {
            $currentRoute = $request->getPathInfo();

            // 如果当前路由不需要锁定，返回简单响应或重定向到根路径
            if (!$this->lockDetector->shouldLockRoute($currentRoute)) {
                return $this->redirect('/');
            }

            // 记录绕过尝试
            $this->lockManager->recordBypassAttempt($currentRoute, 'direct_access');
        }

        $redirectUrl = $request->query->get('redirect');
        $lockedRoute = $this->lockManager->getLockedRoute();

        return $this->render('@IdleLockScreen/lock_screen.html.twig', [
            'redirect_url' => $redirectUrl,
            'locked_route' => $lockedRoute,
            'lock_time' => $this->lockManager->getLockTime(),
        ]);
    }
}
