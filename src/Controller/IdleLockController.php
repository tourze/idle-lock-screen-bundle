<?php

namespace Tourze\IdleLockScreenBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;
use Tourze\IdleLockScreenBundle\Service\LockManager;

/**
 * 无操作锁定控制器
 */
#[Route('/idle-lock', name: 'idle_lock_')]
class IdleLockController extends AbstractController
{
    public function __construct(
        private readonly LockManager $lockManager,
        private readonly IdleLockDetector $lockDetector,
        private readonly Security $security
    ) {
    }

    /**
     * 处理超时锁定请求
     */
    #[Route('/timeout', name: 'timeout', methods: ['POST', 'GET'])]
    public function timeout(Request $request): Response
    {
        // POST 请求：处理 JavaScript 发送的锁定请求
        if ($request->isMethod('POST')) {
            return $this->handleTimeoutRequest($request);
        }

        // GET 请求：显示锁定页面
        return $this->showLockScreen($request);
    }

    /**
     * 处理解锁验证请求
     */
    #[Route('/unlock', name: 'unlock', methods: ['POST'])]
    public function unlock(Request $request): Response
    {
        if (!$this->lockManager->isSessionLocked()) {
            return $this->redirectToRoute('app_dashboard'); // 假设有一个默认首页路由
        }

        $password = $request->request->get('password');
        $redirectUrl = $request->request->get('redirect_url');

        if (empty($password)) {
            $this->addFlash('error', '请输入密码');
            return $this->redirectToRoute('idle_lock_timeout', [
                'redirect' => $redirectUrl
            ]);
        }

        // 验证密码
        if ($this->verifyPassword($password)) {
            $this->lockManager->unlockSession($redirectUrl);

            // 跳转回原页面
            if ($redirectUrl && $this->isValidRedirectUrl($redirectUrl)) {
                return $this->redirect($redirectUrl);
            }

            return $this->redirectToRoute('app_dashboard');
        }

        $this->addFlash('error', '密码错误，请重试');
        return $this->redirectToRoute('idle_lock_timeout', [
            'redirect' => $redirectUrl
        ]);
    }

    /**
     * 获取锁定状态（AJAX接口）
     */
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse([
            'locked' => $this->lockManager->isSessionLocked(),
            'locked_route' => $this->lockManager->getLockedRoute(),
            'lock_time' => $this->lockManager->getLockTime(),
        ]);
    }

    /**
     * 处理超时锁定请求
     */
    private function handleTimeoutRequest(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $route = $data['route'] ?? $request->getPathInfo();

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

    /**
     * 显示锁定页面
     */
    private function showLockScreen(Request $request): Response
    {
        // 如果没有被锁定，检查是否应该被锁定
        if (!$this->lockManager->isSessionLocked()) {
            $currentRoute = $request->getPathInfo();
            
            // 如果当前路由不需要锁定，重定向到首页
            if (!$this->lockDetector->shouldLockRoute($currentRoute)) {
                return $this->redirectToRoute('app_dashboard');
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

    /**
     * 验证密码
     */
    private function verifyPassword(string $password): bool
    {
        $user = $this->security->getUser();
        if (!$user) {
            return false;
        }

        // 这里需要根据实际的用户系统来验证密码
        // 示例：如果用户有 checkPassword 方法
        if (method_exists($user, 'checkPassword')) {
            return call_user_func([$user, 'checkPassword'], $password);
        }

        // 或者使用 Symfony 的密码验证器
        // 这里需要注入 UserPasswordHasherInterface
        // return $this->passwordHasher->isPasswordValid($user, $password);

        // 临时实现：总是返回 true（生产环境中需要实现真正的密码验证）
        return true;
    }

    /**
     * 验证重定向URL是否安全
     */
    private function isValidRedirectUrl(string $url): bool
    {
        // 防止开放重定向攻击
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            // 检查是否是同域名
            $host = parse_url($url, PHP_URL_HOST);
            $currentHost = $this->getParameter('app.domain') ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
            
            return $host === $currentHost;
        }

        // 相对URL是安全的
        return str_starts_with($url, '/');
    }
}
