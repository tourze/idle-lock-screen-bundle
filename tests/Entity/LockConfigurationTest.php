<?php

namespace Tourze\IdleLockScreenBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;

/**
 * LockConfiguration 实体测试
 */
class LockConfigurationTest extends TestCase
{
    private LockConfiguration $lockConfiguration;

    protected function setUp(): void
    {
        $this->lockConfiguration = new LockConfiguration();
    }

    public function test_construct_setsDefaultValues(): void
    {
        $config = new LockConfiguration();
        
        $this->assertEquals(60, $config->getTimeoutSeconds());
        $this->assertTrue($config->isEnabled());
        $this->assertNull($config->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getUpdatedAt());
    }

    public function test_setRoutePattern_updatesPatternAndTimestamp(): void
    {
        $pattern = '/billing/*';
        $originalUpdatedAt = $this->lockConfiguration->getUpdatedAt();
        
        // 确保时间差异
        usleep(1000);
        
        $result = $this->lockConfiguration->setRoutePattern($pattern);
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertEquals($pattern, $this->lockConfiguration->getRoutePattern());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdatedAt());
    }

    public function test_setTimeoutSeconds_withValidValue(): void
    {
        $timeout = 120;
        $originalUpdatedAt = $this->lockConfiguration->getUpdatedAt();
        
        usleep(1000);
        
        $result = $this->lockConfiguration->setTimeoutSeconds($timeout);
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertEquals($timeout, $this->lockConfiguration->getTimeoutSeconds());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdatedAt());
    }

    public function test_setTimeoutSeconds_withZeroValue(): void
    {
        $timeout = 0;
        
        $result = $this->lockConfiguration->setTimeoutSeconds($timeout);
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertEquals($timeout, $this->lockConfiguration->getTimeoutSeconds());
    }

    public function test_setTimeoutSeconds_withNegativeValue(): void
    {
        $timeout = -10;
        
        $result = $this->lockConfiguration->setTimeoutSeconds($timeout);
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertEquals($timeout, $this->lockConfiguration->getTimeoutSeconds());
    }

    public function test_setIsEnabled_togglesState(): void
    {
        $originalUpdatedAt = $this->lockConfiguration->getUpdatedAt();
        
        usleep(1000);
        
        // 禁用
        $result = $this->lockConfiguration->setIsEnabled(false);
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertFalse($this->lockConfiguration->isEnabled());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdatedAt());
        
        $secondUpdatedAt = $this->lockConfiguration->getUpdatedAt();
        usleep(1000);
        
        // 重新启用
        $this->lockConfiguration->setIsEnabled(true);
        
        $this->assertTrue($this->lockConfiguration->isEnabled());
        $this->assertGreaterThan($secondUpdatedAt, $this->lockConfiguration->getUpdatedAt());
    }

    public function test_setDescription_withValidString(): void
    {
        $description = '账单页面锁定配置';
        $originalUpdatedAt = $this->lockConfiguration->getUpdatedAt();
        
        usleep(1000);
        
        $result = $this->lockConfiguration->setDescription($description);
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertEquals($description, $this->lockConfiguration->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdatedAt());
    }

    public function test_setDescription_withNullValue(): void
    {
        // 先设置一个描述
        $this->lockConfiguration->setDescription('测试描述');
        $originalUpdatedAt = $this->lockConfiguration->getUpdatedAt();
        
        usleep(1000);
        
        $result = $this->lockConfiguration->setDescription(null);
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertNull($this->lockConfiguration->getDescription());
        $this->assertGreaterThan($originalUpdatedAt, $this->lockConfiguration->getUpdatedAt());
    }

    public function test_setDescription_withEmptyString(): void
    {
        $result = $this->lockConfiguration->setDescription('');
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertEquals('', $this->lockConfiguration->getDescription());
    }

    public function test_setDescription_withLongString(): void
    {
        $longDescription = str_repeat('很长的描述文本', 50);
        
        $result = $this->lockConfiguration->setDescription($longDescription);
        
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertEquals($longDescription, $this->lockConfiguration->getDescription());
    }

    public function test_getCreatedAt_remainsConstant(): void
    {
        $originalCreatedAt = $this->lockConfiguration->getCreatedAt();
        
        // 修改配置
        $this->lockConfiguration->setRoutePattern('/test');
        $this->lockConfiguration->setTimeoutSeconds(180);
        
        $this->assertEquals($originalCreatedAt, $this->lockConfiguration->getCreatedAt());
    }

    public function test_getUpdatedAt_changesWithModifications(): void
    {
        $originalUpdatedAt = $this->lockConfiguration->getUpdatedAt();
        
        usleep(1000);
        $this->lockConfiguration->setRoutePattern('/test');
        $firstUpdate = $this->lockConfiguration->getUpdatedAt();
        
        usleep(1000);
        $this->lockConfiguration->setTimeoutSeconds(180);
        $secondUpdate = $this->lockConfiguration->getUpdatedAt();
        
        $this->assertGreaterThan($originalUpdatedAt, $firstUpdate);
        $this->assertGreaterThan($firstUpdate, $secondUpdate);
    }

    /**
     * 测试方法链式调用
     */
    public function test_methodChaining(): void
    {
        $result = $this->lockConfiguration
            ->setRoutePattern('/admin/*')
            ->setTimeoutSeconds(30)
            ->setIsEnabled(true)
            ->setDescription('管理后台锁定');
            
        $this->assertSame($this->lockConfiguration, $result);
        $this->assertEquals('/admin/*', $this->lockConfiguration->getRoutePattern());
        $this->assertEquals(30, $this->lockConfiguration->getTimeoutSeconds());
        $this->assertTrue($this->lockConfiguration->isEnabled());
        $this->assertEquals('管理后台锁定', $this->lockConfiguration->getDescription());
    }

    /**
     * 测试特殊字符路由模式
     */
    public function test_setRoutePattern_withSpecialCharacters(): void
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
    public function test_setTimeoutSeconds_extremeValues(): void
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
} 