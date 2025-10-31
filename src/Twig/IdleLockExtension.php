<?php

namespace Tourze\IdleLockScreenBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;
use Tourze\IdleLockScreenBundle\Service\LockManager;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig 扩展
 * 提供无操作锁定相关的 Twig 函数
 */
class IdleLockExtension extends AbstractExtension
{
    public function __construct(
        private IdleLockDetector $lockDetector,
        private LockManager $lockManager,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
    ) {
    }

    /**
     * 渲染无操作锁定的 JavaScript 代码
     */
    public function renderIdleLockScript(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return '';
        }

        $route = $request->getPathInfo();

        // 检查当前路由是否需要锁定
        if (!$this->lockDetector->shouldLockRoute($route)) {
            return '';
        }

        // 如果已经被锁定，不需要再注入脚本
        if ($this->lockManager->isSessionLocked()) {
            return '';
        }

        $timeout = $this->lockDetector->getRouteTimeout($route);
        $lockUrl = $this->urlGenerator->generate('idle_lock_timeout');

        return $this->generateJavaScript($timeout, $lockUrl, $route);
    }

    /**
     * 检查当前路由是否启用了无操作锁定
     */
    public function isIdleLockEnabled(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $route = $request->getPathInfo();

        return $this->lockDetector->shouldLockRoute($route);
    }

    /**
     * 获取当前路由的超时时间
     */
    public function getIdleLockTimeout(): int
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return 60;
        }

        $route = $request->getPathInfo();

        return $this->lockDetector->getRouteTimeout($route);
    }

    /**
     * 生成 JavaScript 代码
     */
    private function generateJavaScript(int $timeout, string $lockUrl, string $route): string
    {
        $timeoutMs = $timeout * 1000; // 转换为毫秒

        return $this->twig->render('@IdleLockScreen/idle_lock_script.html.twig', [
            'timeoutMs' => $timeoutMs,
            'lockUrl' => $lockUrl,
            'route' => $route,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('idle_lock_script', $this->renderIdleLockScript(...), ['is_safe' => ['html']]),
            new TwigFunction('is_idle_lock_enabled', $this->isIdleLockEnabled(...)),
            new TwigFunction('idle_lock_timeout', $this->getIdleLockTimeout(...)),
        ];
    }
}
