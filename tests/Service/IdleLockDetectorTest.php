<?php

namespace Tourze\IdleLockScreenBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * IdleLockDetector 集成测试用例
 *
 * @internal
 */
#[CoversClass(IdleLockDetector::class)]
#[RunTestsInSeparateProcesses]
final class IdleLockDetectorTest extends AbstractIntegrationTestCase
{
    private ?IdleLockDetector $detector = null;

    protected function onSetUp(): void
    {
        // 集成测试设置
    }

    public function getDetector(): IdleLockDetector
    {
        if (null === $this->detector) {
            $detector = self::getContainer()->get(IdleLockDetector::class);
            $this->assertInstanceOf(IdleLockDetector::class, $detector);
            $this->detector = $detector;
        }

        return $this->detector;
    }

    /**
     * 测试创建配置
     */
    public function testCreateConfiguration(): void
    {
        $result = $this->getDetector()->createConfiguration(
            '/api/*',
            90,
            true,
            'API routes lock'
        );

        $this->assertInstanceOf(LockConfiguration::class, $result);
        $this->assertEquals('/api/*', $result->getRoutePattern());
        $this->assertEquals(90, $result->getTimeoutSeconds());
        $this->assertTrue($result->isEnabled());
        $this->assertEquals('API routes lock', $result->getDescription());

        // 验证数据库持久化
        self::getEntityManager()->refresh($result);
        $this->assertEquals('/api/*', $result->getRoutePattern());
    }

    /**
     * 测试创建配置 - 使用默认值
     */
    public function testCreateConfigurationWithDefaults(): void
    {
        $result = $this->getDetector()->createConfiguration('/test/*');

        $this->assertEquals('/test/*', $result->getRoutePattern());
        $this->assertEquals(60, $result->getTimeoutSeconds());
        $this->assertTrue($result->isEnabled());
        $this->assertNull($result->getDescription());
    }

    /**
     * 测试路由是否需要锁定 - 有匹配配置
     */
    public function testShouldLockRouteWithMatchingConfiguration(): void
    {
        // 创建配置
        $this->getDetector()->createConfiguration('/billing/*', 60, true);

        $result = $this->getDetector()->shouldLockRoute('/billing/invoice');

        $this->assertTrue($result);
    }

    /**
     * 测试路由是否需要锁定 - 无匹配配置
     */
    public function testShouldLockRouteWithoutMatchingConfiguration(): void
    {
        // 创建不匹配的配置
        $this->getDetector()->createConfiguration('/admin/*', 60, true);

        $result = $this->getDetector()->shouldLockRoute('/billing/invoice');

        $this->assertFalse($result);
    }

    /**
     * 测试路由是否需要锁定 - 空配置
     */
    public function testShouldLockRouteWithEmptyConfigurations(): void
    {
        $result = $this->getDetector()->shouldLockRoute('/any/route');

        $this->assertFalse($result);
    }

    /**
     * 测试获取路由配置 - 有匹配
     */
    public function testGetRouteConfigurationWithMatch(): void
    {
        $config1 = $this->getDetector()->createConfiguration('/admin/*', 30, true);
        $config2 = $this->getDetector()->createConfiguration('/billing/*', 60, true);

        $result = $this->getDetector()->getRouteConfiguration('/billing/invoice');

        $this->assertSame($config2, $result);
    }

    /**
     * 测试获取路由配置 - 无匹配
     */
    public function testGetRouteConfigurationWithoutMatch(): void
    {
        $this->getDetector()->createConfiguration('/admin/*', 60, true);

        $result = $this->getDetector()->getRouteConfiguration('/public/home');

        $this->assertNull($result);
    }

    /**
     * 测试获取路由超时时间 - 有配置
     */
    public function testGetRouteTimeoutWithConfiguration(): void
    {
        $this->getDetector()->createConfiguration('/billing/*', 120, true);

        $result = $this->getDetector()->getRouteTimeout('/billing/invoice');

        $this->assertEquals(120, $result);
    }

    /**
     * 测试获取路由超时时间 - 无配置（默认值）
     */
    public function testGetRouteTimeoutWithoutConfiguration(): void
    {
        $result = $this->getDetector()->getRouteTimeout('/public/home');

        $this->assertEquals(60, $result);
    }

    /**
     * 测试获取所有启用的配置
     */
    public function testGetEnabledConfigurations(): void
    {
        // 清理所有现有配置
        self::getEntityManager()
            ->createQuery('DELETE FROM ' . LockConfiguration::class)
            ->execute()
        ;

        $config1 = $this->getDetector()->createConfiguration('/admin/*', 60, true);
        $config2 = $this->getDetector()->createConfiguration('/billing/*', 120, true);
        $this->getDetector()->createConfiguration('/disabled/*', 90, false);

        $result = $this->getDetector()->getEnabledConfigurations();

        $this->assertCount(2, $result);
        $this->assertContains($config1, $result);
        $this->assertContains($config2, $result);
    }

    /**
     * 测试获取所有配置（包括禁用的）
     */
    public function testGetAllConfigurations(): void
    {
        // 清理所有现有配置
        self::getEntityManager()
            ->createQuery('DELETE FROM ' . LockConfiguration::class)
            ->execute()
        ;

        $config1 = $this->getDetector()->createConfiguration('/admin/*', 60, true);
        $config2 = $this->getDetector()->createConfiguration('/billing/*', 120, false);

        $result = $this->getDetector()->getAllConfigurations();

        $this->assertCount(2, $result);
        $this->assertContains($config1, $result);
        $this->assertContains($config2, $result);
    }

    /**
     * 测试更新配置 - 全部参数
     */
    public function testUpdateConfigurationAllParameters(): void
    {
        $config = $this->getDetector()->createConfiguration('/old/*', 60, true, 'Old description');

        $result = $this->getDetector()->updateConfiguration(
            $config,
            '/new/*',
            120,
            false,
            'New description'
        );

        $this->assertSame($config, $result);
        $this->assertEquals('/new/*', $config->getRoutePattern());
        $this->assertEquals(120, $config->getTimeoutSeconds());
        $this->assertFalse($config->isEnabled());
        $this->assertEquals('New description', $config->getDescription());
    }

    /**
     * 测试更新配置 - 部分参数
     */
    public function testUpdateConfigurationPartialParameters(): void
    {
        $config = $this->getDetector()->createConfiguration('/test/*', 60, true, 'Test');
        $originalPattern = $config->getRoutePattern();
        $originalEnabled = $config->isEnabled();
        $originalDescription = $config->getDescription();

        $result = $this->getDetector()->updateConfiguration(
            $config,
            null,
            120,
            null,
            null
        );

        $this->assertSame($config, $result);
        $this->assertEquals($originalPattern, $config->getRoutePattern());
        $this->assertEquals(120, $config->getTimeoutSeconds());
        $this->assertEquals($originalEnabled, $config->isEnabled());
        $this->assertEquals($originalDescription, $config->getDescription());
    }

    /**
     * 测试删除配置
     */
    public function testDeleteConfiguration(): void
    {
        $config = $this->getDetector()->createConfiguration('/test/*', 60, true);
        $configId = $config->getId();
        $this->assertNotNull($configId);

        $this->getDetector()->deleteConfiguration($config);

        // 验证配置已删除
        $result = $this->getDetector()->getConfigurationById($configId);
        $this->assertNull($result);
    }

    /**
     * 测试根据ID获取配置
     */
    public function testGetConfigurationById(): void
    {
        $config = $this->getDetector()->createConfiguration('/test/*', 60, true);
        $configId = $config->getId();
        $this->assertNotNull($configId);

        $result = $this->getDetector()->getConfigurationById($configId);

        $this->assertSame($config, $result);
    }

    /**
     * 测试根据ID获取配置 - 不存在
     */
    public function testGetConfigurationByIdNotFound(): void
    {
        $result = $this->getDetector()->getConfigurationById(999999);

        $this->assertNull($result);
    }

    /**
     * 测试验证路由模式
     */
    public function testValidateRoutePattern(): void
    {
        $this->assertTrue($this->getDetector()->validateRoutePattern('/valid/*'));
        $this->assertTrue($this->getDetector()->validateRoutePattern('/admin/**'));
        $this->assertTrue($this->getDetector()->validateRoutePattern('^/api/.*'));
        $this->assertFalse($this->getDetector()->validateRoutePattern(''));
        $this->assertFalse($this->getDetector()->validateRoutePattern('/invalid[unclosed'));
    }

    /**
     * 测试获取匹配的配置
     */
    public function testGetMatchingConfigurations(): void
    {
        $config1 = $this->getDetector()->createConfiguration('/admin/*', 60, true);
        $config2 = $this->getDetector()->createConfiguration('/billing/*', 120, true);
        $config3 = $this->getDetector()->createConfiguration('/api/*', 90, true);

        $result = $this->getDetector()->getMatchingConfigurations('/billing/invoice');

        $this->assertCount(1, $result);
        $this->assertContains($config2, $result);
    }

    /**
     * 测试获取匹配的配置 - 多个匹配
     */
    public function testGetMatchingConfigurationsMultipleMatches(): void
    {
        $config1 = $this->getDetector()->createConfiguration('/billing/*', 60, true);
        $config2 = $this->getDetector()->createConfiguration('/billing/**', 120, true);

        $result = $this->getDetector()->getMatchingConfigurations('/billing/invoice');

        $this->assertCount(2, $result);
        $this->assertContains($config1, $result);
        $this->assertContains($config2, $result);
    }

    /**
     * 测试批量切换配置状态
     */
    public function testToggleConfigurations(): void
    {
        $config1 = $this->getDetector()->createConfiguration('/test1/*', 60, true);
        $config2 = $this->getDetector()->createConfiguration('/test2/*', 60, true);
        $config3 = $this->getDetector()->createConfiguration('/test3/*', 60, true);

        $config1Id = $config1->getId();
        $config2Id = $config2->getId();
        $this->assertNotNull($config1Id);
        $this->assertNotNull($config2Id);
        $configIds = [$config1Id, $config2Id];

        $result = $this->getDetector()->toggleConfigurations($configIds, false);

        $this->assertEquals(2, $result);

        // 验证配置状态已更新
        self::getEntityManager()->refresh($config1);
        self::getEntityManager()->refresh($config2);
        self::getEntityManager()->refresh($config3);

        $this->assertFalse($config1->isEnabled());
        $this->assertFalse($config2->isEnabled());
        $this->assertTrue($config3->isEnabled()); // 未被更新
    }

    /**
     * 测试批量切换配置状态 - 空数组
     */
    public function testToggleConfigurationsEmptyArray(): void
    {
        $result = $this->getDetector()->toggleConfigurations([], true);

        $this->assertEquals(0, $result);
    }

    /**
     * 测试复杂路由模式匹配
     */
    public function testComplexRoutePatternMatching(): void
    {
        // 通配符模式
        $this->getDetector()->createConfiguration('/admin/*', 60, true);
        $this->assertTrue($this->getDetector()->shouldLockRoute('/admin/users'));
        $this->assertTrue($this->getDetector()->shouldLockRoute('/admin/settings'));
        $this->assertFalse($this->getDetector()->shouldLockRoute('/admin/users/edit'));

        // 多级通配符模式
        $this->getDetector()->createConfiguration('/api/**', 90, true);
        $this->assertTrue($this->getDetector()->shouldLockRoute('/api/v1/users'));
        $this->assertTrue($this->getDetector()->shouldLockRoute('/api/v2/posts/123/comments'));

        // 正则表达式模式
        $this->getDetector()->createConfiguration('^/billing/(invoice|payment)', 120, true);
        $this->assertTrue($this->getDetector()->shouldLockRoute('/billing/invoice'));
        $this->assertTrue($this->getDetector()->shouldLockRoute('/billing/payment'));
        $this->assertFalse($this->getDetector()->shouldLockRoute('/billing/report'));
    }
}
