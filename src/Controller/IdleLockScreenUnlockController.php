<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\IdleLockScreenBundle\Service\LockManager;

final class IdleLockScreenUnlockController extends AbstractController
{
    public function __construct(
        private readonly LockManager $lockManager,
        private readonly Security $security,
    ) {
    }

    #[Route(path: '/idle-lock/unlock', name: 'idle_lock_unlock', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        if (!$this->lockManager->isSessionLocked()) {
            return $this->redirect('/');
        }

        $passwordRaw = $request->request->get('password');
        $redirectUrlRaw = $request->request->get('redirect_url');

        // 确保密码是字符串类型
        if (!is_string($passwordRaw) || '' === $passwordRaw) {
            $this->addFlash('danger', '请输入密码');

            return $this->redirectToRoute('idle_lock_lock_screen', [
                'redirect' => is_string($redirectUrlRaw) ? $redirectUrlRaw : null,
            ]);
        }

        // 确保重定向URL是字符串或null
        $redirectUrl = is_string($redirectUrlRaw) ? $redirectUrlRaw : null;

        // 验证密码
        if ($this->verifyPassword($passwordRaw)) {
            $this->lockManager->unlockSession($redirectUrl);

            // 跳转回原页面
            if (null !== $redirectUrl && $this->isValidRedirectUrl($redirectUrl)) {
                return $this->redirect($redirectUrl);
            }

            return $this->redirect('/');
        }

        $this->addFlash('danger', '密码错误，请重试');

        return $this->redirectToRoute('idle_lock_lock_screen', [
            'redirect' => $redirectUrl,
        ]);
    }

    private function verifyPassword(string $password): bool
    {
        $user = $this->security->getUser();
        if (null === $user) {
            return false;
        }

        // 这里需要根据实际的用户系统来验证密码
        // 示例：如果用户有 checkPassword 方法
        if (method_exists($user, 'checkPassword')) {
            return (bool) call_user_func([$user, 'checkPassword'], $password);
        }

        // 或者使用 Symfony 的密码验证器
        // 这里需要注入 UserPasswordHasherInterface
        // return $this->passwordHasher->isPasswordValid($user, $password);

        // 临时实现：总是返回 true（生产环境中需要实现真正的密码验证）
        return true;
    }

    private function isValidRedirectUrl(string $url): bool
    {
        // 防止开放重定向攻击
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            // 检查是否是同域名
            $host = parse_url($url, PHP_URL_HOST);
            $currentHost = $this->getParameter('app.domain') ?? 'localhost';

            return $host === $currentHost;
        }

        // 相对URL是安全的
        return str_starts_with($url, '/');
    }
}
