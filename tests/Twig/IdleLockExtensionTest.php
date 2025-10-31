<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;
use Tourze\IdleLockScreenBundle\Service\LockManager;
use Tourze\IdleLockScreenBundle\Twig\IdleLockExtension;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Twig\Environment;

/**
 * @internal
 *
 * Twig扩展测试 - 遵循静态分析要求继承AbstractIntegrationTestCase
 * 虽然主要使用Mock进行单元测试，但为了满足服务测试的规范要求，
 * 继承AbstractIntegrationTestCase并使用RunTestsInSeparateProcesses注解
 */
#[CoversClass(IdleLockExtension::class)]
#[RunTestsInSeparateProcesses]
final class IdleLockExtensionTest extends AbstractIntegrationTestCase
{
    private IdleLockExtension $extension;

    private IdleLockDetector $lockDetector;

    private LockManager $lockManager;

    private RequestStack $requestStack;

    private UrlGeneratorInterface $urlGenerator;

    private Environment $twig;

    protected function onSetUp(): void
    {
        // 在集成测试中，从容器获取真实的服务实例
        // 不使用Mock，而是测试真实的集成行为
        $this->extension = self::getService(IdleLockExtension::class);

        // 获取真实的依赖服务用于测试验证
        $this->lockDetector = self::getService(IdleLockDetector::class);
        $this->lockManager = self::getService(LockManager::class);
        $this->requestStack = self::getService(RequestStack::class);
        $this->urlGenerator = self::getService(UrlGeneratorInterface::class);
        $this->twig = self::getService(Environment::class);
    }

    /**
     * 测试getFunctions返回正确的Twig函数
     */
    public function testGetFunctionsReturnsCorrectTwigFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(3, $functions);

        $functionNames = array_map(fn ($func) => $func->getName(), $functions);

        $this->assertContains('idle_lock_script', $functionNames);
        $this->assertContains('is_idle_lock_enabled', $functionNames);
        $this->assertContains('idle_lock_timeout', $functionNames);
    }

    /**
     * 测试isIdleLockEnabled - 没有请求时返回false
     */
    public function testIsIdleLockEnabledReturnsFalseWithoutRequest(): void
    {
        // 在集成测试中，验证真实行为：没有请求时返回false
        $result = $this->extension->isIdleLockEnabled();

        $this->assertFalse($result);
    }

    /**
     * 测试getIdleLockTimeout - 没有请求时返回默认值
     */
    public function testGetIdleLockTimeoutReturnsDefaultWithoutRequest(): void
    {
        // 在集成测试中，验证真实行为：没有请求时返回默认超时时间
        $result = $this->extension->getIdleLockTimeout();

        $this->assertEquals(60, $result);
    }

    /**
     * 测试renderIdleLockScript - 没有请求时返回空字符串
     */
    public function testRenderIdleLockScriptReturnsEmptyStringWithoutRequest(): void
    {
        // 在集成测试中，验证真实行为：没有请求时返回空字符串
        $result = $this->extension->renderIdleLockScript();

        $this->assertEquals('', $result);
    }

    /**
     * 测试扩展可以正常实例化并提供基础功能
     */
    public function testExtensionCanBeInstantiatedAndProvidesBasicFunctionality(): void
    {
        // 验证扩展实例化正常
        $this->assertInstanceOf(IdleLockExtension::class, $this->extension);

        // 验证依赖服务注入正常
        $this->assertInstanceOf(IdleLockDetector::class, $this->lockDetector);
        $this->assertInstanceOf(LockManager::class, $this->lockManager);
        $this->assertInstanceOf(RequestStack::class, $this->requestStack);
        $this->assertInstanceOf(UrlGeneratorInterface::class, $this->urlGenerator);
        $this->assertInstanceOf(Environment::class, $this->twig);
    }

    /**
     * 测试扩展提供的Twig函数可以被正常调用
     */
    public function testTwigFunctionsCanBeCalled(): void
    {
        $functions = $this->extension->getFunctions();

        foreach ($functions as $function) {
            $this->assertIsCallable($function->getCallable());
        }
    }
}
