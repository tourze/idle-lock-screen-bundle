<?php

namespace Tourze\IdleLockScreenBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(LockConfiguration::class)]
final class LockConfigurationTest extends AbstractEntityTestCase
{
    private LockConfiguration $lockConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockConfiguration = new LockConfiguration();
    }

    public function testConstructSetsDefaultValues(): void
    {
        $config = new LockConfiguration();

        $this->assertEquals(60, $config->getTimeoutSeconds());
        $this->assertTrue($config->isEnabled());
        $this->assertNull($config->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getUpdateTime());
    }

    public function testSetRoutePatternUpdatesPatternAndTimestamp(): void
    {
        $pattern = '/billing/*';
        $originalUpdatedAt = $this->lockConfiguration->getUpdateTime();

        // 确保时间差异
        usleep(1000);

        $this->lockConfiguration->setRoutePattern($pattern);
        $this->assertEquals($pattern, $this->lockConfiguration->getRoutePattern());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdateTime());
    }

    public function testSetTimeoutSecondsWithValidValue(): void
    {
        $timeout = 120;
        $originalUpdatedAt = $this->lockConfiguration->getUpdateTime();

        usleep(1000);

        $this->lockConfiguration->setTimeoutSeconds($timeout);
        $this->assertEquals($timeout, $this->lockConfiguration->getTimeoutSeconds());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdateTime());
    }

    public function testSetTimeoutSecondsWithZeroValue(): void
    {
        $timeout = 0;

        $this->lockConfiguration->setTimeoutSeconds($timeout);
        $this->assertEquals($timeout, $this->lockConfiguration->getTimeoutSeconds());
    }

    public function testSetTimeoutSecondsWithNegativeValue(): void
    {
        $timeout = -10;

        $this->lockConfiguration->setTimeoutSeconds($timeout);
        $this->assertEquals($timeout, $this->lockConfiguration->getTimeoutSeconds());
    }

    public function testSetIsEnabledTogglesState(): void
    {
        $originalUpdatedAt = $this->lockConfiguration->getUpdateTime();

        usleep(1000);

        // 禁用
        $this->lockConfiguration->setIsEnabled(false);
        $this->assertFalse($this->lockConfiguration->isEnabled());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdateTime());

        $secondUpdatedAt = $this->lockConfiguration->getUpdateTime();
        usleep(1000);

        // 重新启用
        $this->lockConfiguration->setIsEnabled(true);

        $this->assertTrue($this->lockConfiguration->isEnabled());
        $this->assertGreaterThan($secondUpdatedAt, $this->lockConfiguration->getUpdateTime());
    }

    public function testSetDescriptionWithValidString(): void
    {
        $description = '账单页面锁定配置';
        $originalUpdatedAt = $this->lockConfiguration->getUpdateTime();

        usleep(1000);

        $this->lockConfiguration->setDescription($description);
        $this->assertEquals($description, $this->lockConfiguration->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdateTime());
    }

    public function testSetDescriptionWithNullValue(): void
    {
        // 先设置一个描述
        $this->lockConfiguration->setDescription('测试描述');
        $originalUpdatedAt = $this->lockConfiguration->getUpdateTime();

        usleep(1000);

        $this->lockConfiguration->setDescription(null);
        $this->assertNull($this->lockConfiguration->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdateTime());
    }

    public function testSetDescriptionWithEmptyString(): void
    {
        $this->lockConfiguration->setDescription('');
        $this->assertEquals('', $this->lockConfiguration->getDescription());
    }

    public function testSetDescriptionWithLongString(): void
    {
        $longDescription = str_repeat('很长的描述文本', 50);

        $this->lockConfiguration->setDescription($longDescription);
        $this->assertEquals($longDescription, $this->lockConfiguration->getDescription());
    }

    public function testGetCreateTimeRemainsConstant(): void
    {
        $originalCreateTime = $this->lockConfiguration->getCreateTime();

        // 修改配置
        $this->lockConfiguration->setRoutePattern('/test');
        $this->lockConfiguration->setTimeoutSeconds(180);

        $this->assertEquals($originalCreateTime, $this->lockConfiguration->getCreateTime());
    }

    public function testGetUpdateTimeChangesWithModifications(): void
    {
        $originalUpdateTime = $this->lockConfiguration->getUpdateTime();

        usleep(1000);
        $this->lockConfiguration->setRoutePattern('/test');
        $firstUpdate = $this->lockConfiguration->getUpdateTime();

        usleep(1000);
        $this->lockConfiguration->setTimeoutSeconds(180);
        $secondUpdate = $this->lockConfiguration->getUpdateTime();

        $this->assertGreaterThan($originalUpdateTime, $firstUpdate);
        $this->assertGreaterThan($firstUpdate, $secondUpdate);
    }

    /**
     * 测试方法设置功能
     */
    public function testMethodChaining(): void
    {
        $this->lockConfiguration->setRoutePattern('/admin/*');
        $this->lockConfiguration->setTimeoutSeconds(30);
        $this->lockConfiguration->setIsEnabled(true);
        $this->lockConfiguration->setDescription('管理后台锁定');

        $this->assertEquals('/admin/*', $this->lockConfiguration->getRoutePattern());
        $this->assertEquals(30, $this->lockConfiguration->getTimeoutSeconds());
        $this->assertTrue($this->lockConfiguration->isEnabled());
        $this->assertEquals('管理后台锁定', $this->lockConfiguration->getDescription());
    }

    /**
     * 测试特殊字符路由模式
     */
    public function testSetRoutePatternWithSpecialCharacters(): void
    {
        $patterns = [
            '^/admin/.*',
            '/billing/(invoice|payment)',
            '/user/{id}',
            '/api/v[0-9]+',
            '/path/with/中文',
            '/path/with spaces',
            '/path/with-dashes_and_underscores',
        ];

        foreach ($patterns as $pattern) {
            $this->lockConfiguration->setRoutePattern($pattern);
            $this->assertEquals($pattern, $this->lockConfiguration->getRoutePattern());
        }
    }

    /**
     * 测试极端超时值
     */
    public function testSetTimeoutSecondsExtremeValues(): void
    {
        $extremeValues = [
            0,
            1,
            86400,      // 1 day
            31536000,   // 1 year
            PHP_INT_MAX,
            PHP_INT_MIN,
        ];

        foreach ($extremeValues as $value) {
            $this->lockConfiguration->setTimeoutSeconds($value);
            $this->assertEquals($value, $this->lockConfiguration->getTimeoutSeconds());
        }
    }

    /**
     * 创建被测实体的实例
     */
    protected function createEntity(): object
    {
        $entity = new LockConfiguration();
        // 设置必需的属性以创建有效实例
        $entity->setRoutePattern('/test/*');

        return $entity;
    }

    /**
     * 提供属性及其样本值的 Data Provider
     * @return iterable<string, array{string, int|string}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'routePattern' => ['routePattern', '/test/*'];
        yield 'timeoutSeconds' => ['timeoutSeconds', 120];
        yield 'description' => ['description', '测试配置'];
    }
}
