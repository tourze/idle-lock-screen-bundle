<?php

namespace Tourze\IdleLockScreenBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;
use Tourze\IdleLockScreenBundle\Service\RoutePatternMatcher;

/**
 * IdleLockDetector 测试用例
 */
class IdleLockDetectorTest extends TestCase
{
    private IdleLockDetector $detector;
    private EntityManagerInterface&MockObject $entityManager;
    private RoutePatternMatcher&MockObject $routePatternMatcher;
    private QueryBuilder&MockObject $queryBuilder;
    private Query&MockObject $query;
    private EntityRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->routePatternMatcher = $this->createMock(RoutePatternMatcher::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->detector = new IdleLockDetector(
            $this->entityManager,
            $this->routePatternMatcher
        );
    }

    /**
     * 测试路由是否需要锁定 - 有匹配配置
     */
    public function test_shouldLockRoute_withMatchingConfiguration(): void
    {
        $config = $this->createLockConfiguration('/billing/*', 60, true);
        
        $this->setupQueryBuilderMock([$config]);
        $this->routePatternMatcher
            ->expects($this->once())
            ->method('matches')
            ->with('/billing/invoice', '/billing/*')
            ->willReturn(true);

        $result = $this->detector->shouldLockRoute('/billing/invoice');
        
        $this->assertTrue($result);
    }

    /**
     * 测试路由是否需要锁定 - 无匹配配置
     */
    public function test_shouldLockRoute_withoutMatchingConfiguration(): void
    {
        $config = $this->createLockConfiguration('/admin/*', 60, true);
        
        $this->setupQueryBuilderMock([$config]);
        $this->routePatternMatcher
            ->expects($this->once())
            ->method('matches')
            ->with('/billing/invoice', '/admin/*')
            ->willReturn(false);

        $result = $this->detector->shouldLockRoute('/billing/invoice');
        
        $this->assertFalse($result);
    }

    /**
     * 测试路由是否需要锁定 - 空配置
     */
    public function test_shouldLockRoute_withEmptyConfigurations(): void
    {
        $this->setupQueryBuilderMock([]);

        $result = $this->detector->shouldLockRoute('/any/route');
        
        $this->assertFalse($result);
    }

    /**
     * 测试获取路由配置 - 有匹配
     */
    public function test_getRouteConfiguration_withMatch(): void
    {
        $config1 = $this->createLockConfiguration('/admin/*', 30, true);
        $config2 = $this->createLockConfiguration('/billing/*', 60, true);
        
        $this->setupQueryBuilderMock([$config1, $config2]);
        $this->routePatternMatcher
            ->expects($this->exactly(2))
            ->method('matches')
            ->willReturnMap([
                ['/billing/invoice', '/admin/*', false],
                ['/billing/invoice', '/billing/*', true],
            ]);

        $result = $this->detector->getRouteConfiguration('/billing/invoice');
        
        $this->assertSame($config2, $result);
    }

    /**
     * 测试获取路由配置 - 无匹配
     */
    public function test_getRouteConfiguration_withoutMatch(): void
    {
        $config = $this->createLockConfiguration('/admin/*', 60, true);
        
        $this->setupQueryBuilderMock([$config]);
        $this->routePatternMatcher
            ->expects($this->once())
            ->method('matches')
            ->with('/public/home', '/admin/*')
            ->willReturn(false);

        $result = $this->detector->getRouteConfiguration('/public/home');
        
        $this->assertNull($result);
    }

    /**
     * 测试获取路由超时时间 - 有配置
     */
    public function test_getRouteTimeout_withConfiguration(): void
    {
        $config = $this->createLockConfiguration('/billing/*', 120, true);
        
        $this->setupQueryBuilderMock([$config]);
        $this->routePatternMatcher
            ->expects($this->once())
            ->method('matches')
            ->with('/billing/invoice', '/billing/*')
            ->willReturn(true);

        $result = $this->detector->getRouteTimeout('/billing/invoice');
        
        $this->assertEquals(120, $result);
    }

    /**
     * 测试获取路由超时时间 - 无配置（默认值）
     */
    public function test_getRouteTimeout_withoutConfiguration(): void
    {
        $this->setupQueryBuilderMock([]);

        $result = $this->detector->getRouteTimeout('/public/home');
        
        $this->assertEquals(60, $result);
    }

    /**
     * 测试获取所有启用的配置
     */
    public function test_getEnabledConfigurations(): void
    {
        $config1 = $this->createLockConfiguration('/admin/*', 60, true);
        $config2 = $this->createLockConfiguration('/billing/*', 120, true);
        
        $this->setupQueryBuilderMock([$config1, $config2]);

        $result = $this->detector->getEnabledConfigurations();
        
        $this->assertCount(2, $result);
        $this->assertContains($config1, $result);
        $this->assertContains($config2, $result);
    }

    /**
     * 测试获取所有配置（包括禁用的）
     */
    public function test_getAllConfigurations(): void
    {
        $config1 = $this->createLockConfiguration('/admin/*', 60, true);
        $config2 = $this->createLockConfiguration('/billing/*', 120, false);
        
        $this->setupQueryBuilderForAllConfigurations([$config1, $config2]);

        $result = $this->detector->getAllConfigurations();
        
        $this->assertCount(2, $result);
        $this->assertContains($config1, $result);
        $this->assertContains($config2, $result);
    }

    /**
     * 测试创建新配置
     */
    public function test_createConfiguration(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(LockConfiguration::class));
        
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->detector->createConfiguration(
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
    }

    /**
     * 测试创建配置 - 使用默认值
     */
    public function test_createConfiguration_withDefaults(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(LockConfiguration::class));
        
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->detector->createConfiguration('/test/*');
        
        $this->assertEquals('/test/*', $result->getRoutePattern());
        $this->assertEquals(60, $result->getTimeoutSeconds());
        $this->assertTrue($result->isEnabled());
        $this->assertNull($result->getDescription());
    }

    /**
     * 测试更新配置 - 全部参数
     */
    public function test_updateConfiguration_allParameters(): void
    {
        $config = $this->createLockConfiguration('/old/*', 60, true, 'Old description');
        
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->detector->updateConfiguration(
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
    public function test_updateConfiguration_partialParameters(): void
    {
        $config = $this->createLockConfiguration('/test/*', 60, true, 'Test');
        $originalPattern = $config->getRoutePattern();
        $originalEnabled = $config->isEnabled();
        $originalDescription = $config->getDescription();
        
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->detector->updateConfiguration(
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
    public function test_deleteConfiguration(): void
    {
        $config = $this->createLockConfiguration('/test/*', 60, true);
        
        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($config);
        
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->detector->deleteConfiguration($config);
    }

    /**
     * 测试根据ID获取配置
     */
    public function test_getConfigurationById(): void
    {
        $config = $this->createLockConfiguration('/test/*', 60, true);
        
        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(LockConfiguration::class)
            ->willReturn($this->repository);
        
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($config);

        $result = $this->detector->getConfigurationById(123);
        
        $this->assertSame($config, $result);
    }

    /**
     * 测试根据ID获取配置 - 不存在
     */
    public function test_getConfigurationById_notFound(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(LockConfiguration::class)
            ->willReturn($this->repository);
        
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->detector->getConfigurationById(999);
        
        $this->assertNull($result);
    }

    /**
     * 测试验证路由模式
     */
    public function test_validateRoutePattern(): void
    {
        $this->routePatternMatcher
            ->expects($this->exactly(2))
            ->method('isValidPattern')
            ->willReturnMap([
                ['/valid/*', true],
                ['/invalid[', false],
            ]);

        $this->assertTrue($this->detector->validateRoutePattern('/valid/*'));
        $this->assertFalse($this->detector->validateRoutePattern('/invalid['));
    }

    /**
     * 测试获取匹配的配置
     */
    public function test_getMatchingConfigurations(): void
    {
        $config1 = $this->createLockConfiguration('/admin/*', 60, true);
        $config2 = $this->createLockConfiguration('/billing/*', 120, true);
        $config3 = $this->createLockConfiguration('/api/*', 90, true);
        
        $this->setupQueryBuilderMock([$config1, $config2, $config3]);
        $this->routePatternMatcher
            ->expects($this->exactly(3))
            ->method('matches')
            ->willReturnMap([
                ['/billing/invoice', '/admin/*', false],
                ['/billing/invoice', '/billing/*', true],
                ['/billing/invoice', '/api/*', false],
            ]);

        $result = $this->detector->getMatchingConfigurations('/billing/invoice');
        
        $this->assertCount(1, $result);
        $this->assertContains($config2, $result);
    }

    /**
     * 测试获取匹配的配置 - 多个匹配
     */
    public function test_getMatchingConfigurations_multipleMatches(): void
    {
        $config1 = $this->createLockConfiguration('/billing/*', 60, true);
        $config2 = $this->createLockConfiguration('/billing/**', 120, true);
        
        $this->setupQueryBuilderMock([$config1, $config2]);
        $this->routePatternMatcher
            ->expects($this->exactly(2))
            ->method('matches')
            ->willReturnMap([
                ['/billing/invoice', '/billing/*', true],
                ['/billing/invoice', '/billing/**', true],
            ]);

        $result = $this->detector->getMatchingConfigurations('/billing/invoice');
        
        $this->assertCount(2, $result);
        $this->assertContains($config1, $result);
        $this->assertContains($config2, $result);
    }

    /**
     * 测试批量切换配置状态
     */
    public function test_toggleConfigurations(): void
    {
        $configIds = [1, 2, 3];
        
        $this->setupQueryBuilderForUpdate();
        $this->query
            ->expects($this->once())
            ->method('execute')
            ->willReturn(3);

        $result = $this->detector->toggleConfigurations($configIds, false);
        
        $this->assertEquals(3, $result);
    }

    /**
     * 测试批量切换配置状态 - 空数组
     */
    public function test_toggleConfigurations_emptyArray(): void
    {
        $this->setupQueryBuilderForUpdate();
        $this->query
            ->expects($this->once())
            ->method('execute')
            ->willReturn(0);

        $result = $this->detector->toggleConfigurations([], true);
        
        $this->assertEquals(0, $result);
    }

    /**
     * 创建测试用的 LockConfiguration 对象
     */
    private function createLockConfiguration(
        string $routePattern,
        int $timeoutSeconds,
        bool $isEnabled,
        ?string $description = null
    ): LockConfiguration {
        $config = new LockConfiguration();
        $config->setRoutePattern($routePattern)
               ->setTimeoutSeconds($timeoutSeconds)
               ->setIsEnabled($isEnabled)
               ->setDescription($description);
        
        return $config;
    }

    /**
     * 设置 QueryBuilder Mock（用于获取启用的配置）
     */
    private function setupQueryBuilderMock(array $configurations): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('lc')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with(LockConfiguration::class, 'lc')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('lc.isEnabled = :enabled')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('enabled', true)
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('lc.routePattern', 'ASC')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($configurations);
    }

    /**
     * 设置 QueryBuilder Mock（用于获取所有配置）
     */
    private function setupQueryBuilderForAllConfigurations(array $configurations): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('lc')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with(LockConfiguration::class, 'lc')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('lc.createdAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($configurations);
    }

    /**
     * 设置 QueryBuilder Mock（用于更新操作）
     */
    private function setupQueryBuilderForUpdate(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('update')
            ->with(LockConfiguration::class, 'lc')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('lc.id IN (:ids)')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
    }
} 