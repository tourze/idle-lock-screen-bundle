<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;
use Tourze\IdleLockScreenBundle\Service\AttributeControllerLoader;

/**
 * AttributeControllerLoader 单元测试用例
 *
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
final class AttributeControllerLoaderTest extends TestCase
{
    private AttributeControllerLoader $loader;

    private LoaderInterface&MockObject $controllerLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new AttributeControllerLoader($this->controllerLoader);
    }

    /**
     * 测试Loader可以正确实例化
     */
    public function testLoaderCanBeInstantiated(): void
    {
        $this->assertInstanceOf(AttributeControllerLoader::class, $this->loader);
    }

    /**
     * 测试load方法返回RouteCollection
     */
    public function testLoadReturnsRouteCollection(): void
    {
        $routeCollection = new RouteCollection();

        $this->controllerLoader
            ->expects($this->exactly(3))
            ->method('load')
            ->willReturn($routeCollection)
        ;

        $result = $this->loader->load('test-resource');

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    /**
     * 测试supports方法正确识别控制器资源
     */
    public function testSupportsReturnsTrueForControllerResource(): void
    {
        $this->assertTrue($this->loader->supports('TestController'));
        $this->assertTrue($this->loader->supports('SomeTestController'));
        $this->assertTrue($this->loader->supports('MyController'));
    }

    /**
     * 测试supports方法正确拒绝非控制器资源
     */
    public function testSupportsReturnsFalseForNonControllerResource(): void
    {
        $this->assertFalse($this->loader->supports('TestService'));
        $this->assertFalse($this->loader->supports('test'));
        $this->assertFalse($this->loader->supports(123));
        $this->assertFalse($this->loader->supports(null));
    }

    /**
     * 测试getResolver方法委托给控制器加载器
     */
    public function testGetResolverDelegatesToControllerLoader(): void
    {
        $resolver = $this->createMock(LoaderResolverInterface::class);

        $this->controllerLoader
            ->expects($this->once())
            ->method('getResolver')
            ->willReturn($resolver)
        ;

        $result = $this->loader->getResolver();

        $this->assertSame($resolver, $result);
    }

    /**
     * 测试setResolver方法委托给控制器加载器
     */
    public function testSetResolverDelegatesToControllerLoader(): void
    {
        $resolver = $this->createMock(LoaderResolverInterface::class);

        $this->controllerLoader
            ->expects($this->once())
            ->method('setResolver')
            ->with($resolver)
        ;

        $this->loader->setResolver($resolver);
    }

    /**
     * 测试autoload方法返回RouteCollection
     */
    public function testAutoloadReturnsRouteCollection(): void
    {
        $routeCollection = new RouteCollection();

        $this->controllerLoader
            ->expects($this->exactly(3))
            ->method('load')
            ->willReturn($routeCollection)
        ;

        $result = $this->loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    /**
     * 测试不同类型资源的支持情况
     */
    public function testSupportsDifferentResourceTypes(): void
    {
        $supportedResources = [
            'UserController',
            'AdminController',
            'ApiController',
            'SomeController',
        ];

        $unsupportedResources = [
            'UserService',
            'AdminHelper',
            'ApiUtil',
            'config.yaml',
            '',
            123,
            true,
            null,
        ];

        foreach ($supportedResources as $resource) {
            $this->assertTrue($this->loader->supports($resource), "应该支持: {$resource}");
        }

        foreach ($unsupportedResources as $resource) {
            $this->assertFalse($this->loader->supports($resource), '不应该支持: ' . var_export($resource, true));
        }
    }
}
