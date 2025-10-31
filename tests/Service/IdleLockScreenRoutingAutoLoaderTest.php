<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\IdleLockScreenBundle\Service\IdleLockScreenRoutingAutoLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * IdleLockScreenRoutingAutoLoader 集成测试用例
 *
 * @internal
 */
#[CoversClass(IdleLockScreenRoutingAutoLoader::class)]
#[RunTestsInSeparateProcesses]
final class IdleLockScreenRoutingAutoLoaderTest extends AbstractIntegrationTestCase
{
    private IdleLockScreenRoutingAutoLoader $autoLoader;

    protected function onSetUp(): void
    {
        $this->autoLoader = self::getService(IdleLockScreenRoutingAutoLoader::class);
    }

    /**
     * 测试可以正确实例化并从容器获取服务
     */
    public function testCanBeInstantiatedFromContainer(): void
    {
        $this->assertInstanceOf(IdleLockScreenRoutingAutoLoader::class, $this->autoLoader);
    }

    /**
     * 测试autoload方法返回有效的路由集合
     */
    public function testAutoloadReturnsValidRouteCollection(): void
    {
        $result = $this->autoLoader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    /**
     * 测试自动加载控制器路由
     */
    public function testAutoloadLoadsControllerRoutes(): void
    {
        $routeCollection = $this->autoLoader->autoload();

        // 验证路由集合包含预期的路由
        $routes = $routeCollection->all();

        // 检查是否找到了 idle_lock_lock_screen 路由
        $this->assertArrayHasKey('idle_lock_lock_screen', $routes);

        $lockScreenRoute = $routes['idle_lock_lock_screen'];
        $this->assertEquals('/idle-lock/timeout', $lockScreenRoute->getPath());
        $this->assertEquals(['GET', 'POST'], $lockScreenRoute->getMethods());
    }

    /**
     * 测试路由集合包含所有必需的控制器路由
     */
    public function testAutoloadLoadsAllControllerRoutes(): void
    {
        $routeCollection = $this->autoLoader->autoload();
        $routes = $routeCollection->all();

        // 验证包含的路由数量合理（至少有一个路由）
        $this->assertGreaterThan(0, count($routes));

        // 验证每个路由都有有效的路径和控制器
        foreach ($routes as $routeName => $route) {
            $this->assertNotEmpty($route->getPath(), "路由 {$routeName} 应该有有效的路径");
            $this->assertNotEmpty($route->getDefaults(), "路由 {$routeName} 应该有控制器配置");
        }
    }

    /**
     * 测试多次调用autoload返回一致的结果
     */
    public function testMultipleAutoloadCallsReturnConsistentResults(): void
    {
        $result1 = $this->autoLoader->autoload();
        $result2 = $this->autoLoader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $result1);
        $this->assertInstanceOf(RouteCollection::class, $result2);

        // 验证路由数量一致
        $this->assertEquals($result1->count(), $result2->count());

        // 验证路由名称一致
        $this->assertEquals(array_keys($result1->all()), array_keys($result2->all()));
    }
}
