<?php

namespace Tourze\IdleLockScreenBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;
use Tourze\IdleLockScreenBundle\Service\LockManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig 扩展
 * 提供无操作锁定相关的 Twig 函数
 */
class IdleLockExtension extends AbstractExtension
{
    public function __construct(
        private readonly IdleLockDetector $lockDetector,
        private readonly LockManager $lockManager,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('idle_lock_script', [$this, 'renderIdleLockScript'], ['is_safe' => ['html']]),
            new TwigFunction('is_idle_lock_enabled', [$this, 'isIdleLockEnabled']),
            new TwigFunction('idle_lock_timeout', [$this, 'getIdleLockTimeout']),
        ];
    }

    /**
     * 渲染无操作锁定的 JavaScript 代码
     */
    public function renderIdleLockScript(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
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
        if (!$request) {
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
        if (!$request) {
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
        
        return <<<HTML
<script type="text/javascript">
(function() {
    'use strict';
    
    // 无操作锁定命名空间
    window.IdleLock = window.IdleLock || {};
    
    // 配置
    const config = {
        timeout: {$timeoutMs},
        lockUrl: '{$lockUrl}',
        currentRoute: '{$route}',
        checkInterval: 1000, // 每秒检查一次
        debounceDelay: 300   // 防抖延迟
    };
    
    let lastActivity = Date.now();
    let isLocked = false;
    let checkTimer = null;
    let debounceTimer = null;
    
    // 活动事件列表
    const activityEvents = [
        'mousedown', 'mousemove', 'keypress', 'scroll', 
        'touchstart', 'click', 'focus', 'blur'
    ];
    
    // 重置活动时间（防抖处理）
    function resetActivity() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        
        debounceTimer = setTimeout(function() {
            lastActivity = Date.now();
        }, config.debounceDelay);
    }
    
    // 检查是否超时
    function checkTimeout() {
        if (isLocked) {
            return;
        }
        
        const now = Date.now();
        const timeSinceLastActivity = now - lastActivity;
        
        if (timeSinceLastActivity >= config.timeout) {
            lockScreen();
        }
    }
    
    // 锁定屏幕
    function lockScreen() {
        if (isLocked) {
            return;
        }
        
        isLocked = true;
        
        // 停止检查定时器
        if (checkTimer) {
            clearInterval(checkTimer);
            checkTimer = null;
        }
        
        // 发送锁定请求到服务器
        fetch(config.lockUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                route: config.currentRoute,
                timestamp: Date.now()
            })
        }).then(function(response) {
            if (response.ok) {
                // 跳转到锁定页面
                window.location.href = config.lockUrl + '?redirect=' + encodeURIComponent(window.location.href);
            }
        }).catch(function(error) {
            console.error('IdleLock: Failed to lock session', error);
            // 即使请求失败也跳转到锁定页面
            window.location.href = config.lockUrl + '?redirect=' + encodeURIComponent(window.location.href);
        });
    }
    
    // 初始化
    function init() {
        // 绑定活动事件
        activityEvents.forEach(function(event) {
            document.addEventListener(event, resetActivity, true);
        });
        
        // 启动检查定时器
        checkTimer = setInterval(checkTimeout, config.checkInterval);
        
        // 初始化活动时间
        resetActivity();
        
        console.log('IdleLock: Initialized with timeout', config.timeout + 'ms');
    }
    
    // 清理函数
    function cleanup() {
        if (checkTimer) {
            clearInterval(checkTimer);
            checkTimer = null;
        }
        
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        
        activityEvents.forEach(function(event) {
            document.removeEventListener(event, resetActivity, true);
        });
    }
    
    // 暴露公共接口
    window.IdleLock.init = init;
    window.IdleLock.cleanup = cleanup;
    window.IdleLock.resetActivity = resetActivity;
    window.IdleLock.isLocked = function() { return isLocked; };
    
    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // 页面卸载时清理
    window.addEventListener('beforeunload', cleanup);
    
})();
</script>
HTML;
    }
}
