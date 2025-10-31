<?php

namespace Tourze\IdleLockScreenBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Tourze\IdleLockScreenBundle\EventListener\IdleLockEventSubscriber;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(IdleLockEventSubscriber::class)]
#[RunTestsInSeparateProcesses]
final class IdleLockEventSubscriberTest extends AbstractEventSubscriberTestCase
{
    private IdleLockEventSubscriber $listener;

    protected function onSetUp(): void
    {
        // 从容器获取监听器实例
        $this->listener = self::getService(IdleLockEventSubscriber::class);
    }

    /**
     * 覆盖此方法以避免数据库初始化
     */
    public static function setUpBeforeClass(): void
    {
        // 在内核启动前设置环境变量，完全禁用 Doctrine
        putenv('DATABASE_URL=');
        putenv('DOCTRINE_DEPRECATIONS_ENABLED=');
        putenv('DOCTRINE_PROXY_AUTOGENERATE=');

        parent::setUpBeforeClass();
    }

    public function testGetSubscribedEventsReturnsCorrectEventMapping(): void
    {
        $events = IdleLockEventSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertEquals(['onKernelRequest', 10], $events[KernelEvents::REQUEST]);
    }

    public function testOnKernelRequestSkipsSubRequests(): void
    {
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        // 子请求应该被跳过，不应该有任何副作用
        $this->listener->onKernelRequest($event);

        // 验证没有响应被设置（表示请求被跳过）
        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestSkipsExcludedRoutes(): void
    {
        $excludedRoutes = [
            'idle_lock_timeout',
            'idle_lock_unlock',
            'idle_lock_status',
            '_profiler',
            '_wdt',
        ];

        $kernel = $this->createMock(HttpKernelInterface::class);

        foreach ($excludedRoutes as $route) {
            $request = new Request();
            $request->attributes->set('_route', $route);
            $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

            $this->listener->onKernelRequest($event);

            // 验证被排除的路由没有被处理
            $this->assertNull($event->getResponse());
        }
    }

    public function testOnKernelRequestSkipsExcludedPaths(): void
    {
        $excludedPaths = [
            '/idle-lock/heartbeat',
            '/_profiler/123456',
            '/_wdt/123456',
            '/favicon.ico',
            '/robots.txt',
        ];

        $kernel = $this->createMock(HttpKernelInterface::class);

        foreach ($excludedPaths as $path) {
            $request = Request::create($path);
            $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

            $this->listener->onKernelRequest($event);

            // 验证被排除的路径没有被处理
            $this->assertNull($event->getResponse());
        }
    }

    public function testOnKernelRequestSkipsStaticResources(): void
    {
        $staticResources = [
            '/assets/style.css',
            '/js/app.js',
            '/images/logo.png',
            '/favicon.ico',
            '/fonts/font.woff2',
        ];

        $kernel = $this->createMock(HttpKernelInterface::class);

        foreach ($staticResources as $resource) {
            $request = Request::create($resource);
            $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

            $this->listener->onKernelRequest($event);

            // 验证静态资源没有被处理
            $this->assertNull($event->getResponse());
        }
    }

    public function testOnKernelRequestSkipsNonLockRelatedAjaxRequests(): void
    {
        $request = Request::create('/api/data', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        // 验证 AJAX 请求没有被处理
        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestSkipsIdleLockPaths(): void
    {
        $request = Request::create('/idle-lock/status', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        // 验证空闲锁定路径没有被处理
        $this->assertNull($event->getResponse());
    }

    public function testListenerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(IdleLockEventSubscriber::class, $this->listener);
    }
}
