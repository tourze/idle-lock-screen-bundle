<?php

namespace Tourze\IdleLockScreenBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;
use Tourze\IdleLockScreenBundle\Service\LockManager;

/**
 * 无操作锁定事件监听器
 * 在每个请求中检查锁定状态
 */
class IdleLockListener implements EventSubscriberInterface
{
    private const EXCLUDED_ROUTES = [
        'idle_lock_timeout',
        'idle_lock_unlock',
        'idle_lock_status',
        '_profiler',
        '_wdt',
    ];

    public function __construct(
        private readonly LockManager $lockManager,
        private readonly IdleLockDetector $lockDetector,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    /**
     * 处理请求事件
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $pathInfo = $request->getPathInfo();

        // 跳过排除的路由
        if ($this->isExcludedRoute($route, $pathInfo)) {
            return;
        }

        // 跳过 AJAX 请求（除非是特定的锁定相关请求）
        if ($request->isXmlHttpRequest() && !$this->isLockRelatedAjax($pathInfo)) {
            return;
        }

        // 检查会话是否被锁定
        if ($this->lockManager->isSessionLocked()) {
            $this->handleLockedSession($event, $pathInfo);
            return;
        }

        // 清除过期的锁定状态（用户重新登录的情况）
        $this->lockManager->clearExpiredLocks();
    }

    /**
     * 处理被锁定的会话
     */
    private function handleLockedSession(RequestEvent $event, string $pathInfo): void
    {
        $request = $event->getRequest();
        
        // 记录绕过尝试
        $this->lockManager->recordBypassAttempt($pathInfo, $request->getMethod());

        // 重定向到锁定页面
        $lockUrl = $this->urlGenerator->generate('idle_lock_timeout', [
            'redirect' => $request->getUri()
        ]);

        $response = new RedirectResponse($lockUrl);
        $event->setResponse($response);
    }

    /**
     * 检查是否为排除的路由
     */
    private function isExcludedRoute(?string $route, string $pathInfo): bool
    {
        // 检查路由名称
        if ($route) {
            foreach (self::EXCLUDED_ROUTES as $excludedRoute) {
                if (str_starts_with($route, $excludedRoute)) {
                    return true;
                }
            }
        }

        // 检查路径
        $excludedPaths = [
            '/idle-lock/',
            '/_profiler/',
            '/_wdt/',
            '/favicon.ico',
            '/robots.txt',
        ];

        foreach ($excludedPaths as $excludedPath) {
            if (str_starts_with($pathInfo, $excludedPath)) {
                return true;
            }
        }

        // 检查静态资源
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/i', $pathInfo)) {
            return true;
        }

        return false;
    }

    /**
     * 检查是否为锁定相关的 AJAX 请求
     */
    private function isLockRelatedAjax(string $pathInfo): bool
    {
        return str_starts_with($pathInfo, '/idle-lock/');
    }
}
