<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Repository;

use Doctrine\ORM\Persisters\Exception\UnrecognizedField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;
use Tourze\IdleLockScreenBundle\Repository\LockConfigurationRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * LockConfigurationRepository 测试用例
 *
 * @internal
 */
#[CoversClass(LockConfigurationRepository::class)]
#[RunTestsInSeparateProcesses]
final class LockConfigurationRepositoryTest extends AbstractRepositoryTestCase
{
    private LockConfigurationRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = $this->getRepository();
        $this->assertInstanceOf(LockConfigurationRepository::class, $this->repository);

        // 确保有基础测试数据
        $this->ensureBasicDataExists();
    }

    protected function getRepository(): LockConfigurationRepository
    {
        // @phpstan-ignore doctrine.noGetRepositoryOutsideService
        /** @var LockConfigurationRepository */
        return self::getEntityManager()->getRepository(LockConfiguration::class);
    }

    /**
     * 加载测试数据
     */
    private function loadTestData(): void
    {
        $em = self::getEntityManager();

        $configuration1 = new LockConfiguration();
        $configuration1->setRoutePattern('/base/test/*');
        $configuration1->setTimeoutSeconds(9999);
        $configuration1->setIsEnabled(false);
        $configuration1->setDescription('基础测试数据1（仅用于count测试）');

        $em->persist($configuration1);

        $configuration2 = new LockConfiguration();
        $configuration2->setRoutePattern('/base/test2/*');
        $configuration2->setTimeoutSeconds(9998);
        $configuration2->setIsEnabled(false);
        $configuration2->setDescription('基础测试数据2（仅用于count测试）');

        $em->persist($configuration2);

        $configuration3 = new LockConfiguration();
        $configuration3->setRoutePattern('/base/test3/*');
        $configuration3->setTimeoutSeconds(9997);
        $configuration3->setIsEnabled(false);
        $configuration3->setDescription('基础测试数据3（已禁用）');

        $em->persist($configuration3);

        $em->flush();
    }

    /**
     * 加载基础数据用于DataFixture测试
     */
    private function ensureBasicDataExists(): void
    {
        if (0 === $this->repository->count()) {
            $this->loadTestData();
        }
    }

    /**
     * 测试Repository继承自ServiceEntityRepository
     */

    /**
     * 基础CRUD测试 - find 方法
     */

    /**
     * findAll 方法测试
     */

    /**
     * findOneBy 方法测试
     */
    public function testFindOneByWithOrderByShouldReturnFirstMatchingEntity(): void
    {
        // Arrange
        $entity1 = $this->createTestLockConfiguration('/pattern1', 60, true, 'Description A');
        $entity2 = $this->createTestLockConfiguration('/pattern2', 120, true, 'Description B');
        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);

        // Act
        $result = $this->repository->findOneBy(['isEnabled' => true], ['timeoutSeconds' => 'ASC']);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(60, $result->getTimeoutSeconds());
    }

    /**
     * 可空字段查询测试
     */
    public function testFindByWithNullDescriptionShouldReturnEntitiesWithNullDescription(): void
    {
        // Arrange
        $entityWithDescription = $this->createTestLockConfiguration('/pattern1', 60, true, 'Has description');
        $entityWithoutDescription = $this->createTestLockConfiguration('/pattern2', 60, true, null);
        $this->persistAndFlush($entityWithDescription);
        $this->persistAndFlush($entityWithoutDescription);

        // Act
        $result = $this->repository->findBy(['description' => null]);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('/pattern2', $result[0]->getRoutePattern());
        $this->assertNull($result[0]->getDescription());
    }

    public function testCountWithNullDescriptionShouldReturnCorrectCount(): void
    {
        // Arrange
        $entityWithDescription = $this->createTestLockConfiguration('/pattern1', 60, true, 'Has description');
        $entityWithoutDescription = $this->createTestLockConfiguration('/pattern2', 60, true, null);
        $this->persistAndFlush($entityWithDescription);
        $this->persistAndFlush($entityWithoutDescription);

        // Act
        $count = $this->repository->count(['description' => null]);

        // Assert
        $this->assertEquals(1, $count);
    }

    public function testFindByWithNullIdShouldReturnEmptyArray(): void
    {
        // Arrange
        $entity = $this->createTestLockConfiguration('/pattern1', 60, true, 'Test entity');
        $this->persistAndFlush($entity);

        // Act - 查询 id 为 null 的记录（应该没有）
        $result = $this->repository->findBy(['id' => null]);

        // Assert
        $this->assertCount(0, $result);
    }

    public function testCountWithNullIdShouldReturnZero(): void
    {
        // Arrange
        $entity = $this->createTestLockConfiguration('/pattern1', 60, true, 'Test entity');
        $this->persistAndFlush($entity);

        // Act - 计算 id 为 null 的记录数（应该为0）
        $count = $this->repository->count(['id' => null]);

        // Assert
        $this->assertEquals(0, $count);
    }

    public function testFindOneByWithOrderByDescShouldReturnLastEntity(): void
    {
        // 清理现有数据确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . LockConfiguration::class)->execute();
        $em->flush();

        // Arrange
        $entity1 = $this->createTestLockConfiguration('/pattern1', 60, true, 'Description A');
        $entity2 = $this->createTestLockConfiguration('/pattern2', 120, true, 'Description B');
        $entity3 = $this->createTestLockConfiguration('/pattern3', 180, true, 'Description C');
        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);
        $this->persistAndFlush($entity3);

        // Act - 按 timeoutSeconds 降序查找第一个
        $result = $this->repository->findOneBy(['isEnabled' => true], ['timeoutSeconds' => 'DESC']);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(180, $result->getTimeoutSeconds());
        $this->assertEquals('/pattern3', $result->getRoutePattern());
    }

    public function testFindOneByWithComplexOrderingShouldReturnCorrectEntity(): void
    {
        // Arrange
        $entity1 = $this->createTestLockConfiguration('/pattern1', 60, true, 'A Description');
        $entity2 = $this->createTestLockConfiguration('/pattern2', 60, true, 'B Description');
        $entity3 = $this->createTestLockConfiguration('/pattern3', 120, true, 'C Description');
        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);
        $this->persistAndFlush($entity3);

        // Act - 先按 timeoutSeconds ASC，再按 description ASC
        $result = $this->repository->findOneBy(
            ['isEnabled' => true],
            ['timeoutSeconds' => 'ASC', 'description' => 'ASC']
        );

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(60, $result->getTimeoutSeconds());
        $this->assertEquals('A Description', $result->getDescription());
    }

    /**
     * PHPStan 要求：findOneBy 排序逻辑测试
     */

    /**
     * PHPStan 要求：findOneBy IS NULL 查询测试
     */

    /**
     * PHPStan 要求：findBy IS NULL 查询测试
     */

    /**
     * PHPStan 要求：count IS NULL 查询测试
     */

    /**
     * 无效字段查询测试
     */
    public function testFindByWithInvalidFieldShouldThrowException(): void
    {
        // Assert
        $this->expectException(UnrecognizedField::class);

        // Act
        $this->repository->findBy(['nonExistentField' => 'value']);
    }

    /**
     * toggleConfigurations 方法测试
     */
    public function testToggleConfigurationsShouldEnableMultipleConfigs(): void
    {
        // Arrange
        $entity1 = $this->createTestLockConfiguration('/pattern1', 60, false);
        $entity2 = $this->createTestLockConfiguration('/pattern2', 120, false);
        $entity3 = $this->createTestLockConfiguration('/pattern3', 180, true); // 已启用
        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);
        $this->persistAndFlush($entity3);

        $configIds = [(int) $entity1->getId(), (int) $entity2->getId()];

        // Act - 启用指定配置
        $affectedRows = $this->repository->toggleConfigurations($configIds, true);

        // Assert
        $this->assertEquals(2, $affectedRows);

        // 刷新实体管理器以获取更新后的数据
        self::getEntityManager()->clear();

        // 验证配置已启用
        $foundEntity1 = $this->repository->find($entity1->getId());
        $this->assertNotNull($foundEntity1);
        $this->assertTrue($foundEntity1->isEnabled());

        $foundEntity2 = $this->repository->find($entity2->getId());
        $this->assertNotNull($foundEntity2);
        $this->assertTrue($foundEntity2->isEnabled());

        // 验证未指定的配置保持不变
        $foundEntity3 = $this->repository->find($entity3->getId());
        $this->assertNotNull($foundEntity3);
        $this->assertTrue($foundEntity3->isEnabled());
    }

    public function testToggleConfigurationsShouldDisableMultipleConfigs(): void
    {
        // Arrange
        $entity1 = $this->createTestLockConfiguration('/pattern1', 60, true);
        $entity2 = $this->createTestLockConfiguration('/pattern2', 120, true);
        $entity3 = $this->createTestLockConfiguration('/pattern3', 180, false); // 已禁用
        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);
        $this->persistAndFlush($entity3);

        $configIds = [(int) $entity1->getId(), (int) $entity2->getId()];

        // Act - 禁用指定配置
        $affectedRows = $this->repository->toggleConfigurations($configIds, false);

        // Assert
        $this->assertEquals(2, $affectedRows);

        // 刷新实体管理器以获取更新后的数据
        self::getEntityManager()->clear();

        // 验证配置已禁用
        $foundEntity1 = $this->repository->find($entity1->getId());
        $this->assertNotNull($foundEntity1);
        $this->assertFalse($foundEntity1->isEnabled());

        $foundEntity2 = $this->repository->find($entity2->getId());
        $this->assertNotNull($foundEntity2);
        $this->assertFalse($foundEntity2->isEnabled());

        // 验证未指定的配置保持不变
        $foundEntity3 = $this->repository->find($entity3->getId());
        $this->assertNotNull($foundEntity3);
        $this->assertFalse($foundEntity3->isEnabled());
    }

    public function testToggleConfigurationsWithEmptyIdsShouldReturnZero(): void
    {
        // Act
        $affectedRows = $this->repository->toggleConfigurations([], true);

        // Assert
        $this->assertEquals(0, $affectedRows);
    }

    public function testToggleConfigurationsWithNonExistentIdsShouldReturnZero(): void
    {
        // Act
        $affectedRows = $this->repository->toggleConfigurations([99999, 100000], true);

        // Assert
        $this->assertEquals(0, $affectedRows);
    }

    /**
     * save 方法测试
     */
    public function testSaveWithValidEntityShouldPersistToDatabase(): void
    {
        // Arrange
        $entity = $this->createTestLockConfiguration('/test/save', 300);

        // Act
        $this->repository->save($entity);

        // Assert
        $this->assertNotNull($entity->getId());
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNotNull($savedEntity);
        $this->assertEquals('/test/save', $savedEntity->getRoutePattern());
        $this->assertEquals(300, $savedEntity->getTimeoutSeconds());
    }

    /**
     * remove 方法测试
     */
    public function testRemoveWithValidEntityShouldDeleteFromDatabase(): void
    {
        // Arrange
        $entity = $this->createTestLockConfiguration('/test/remove');
        $this->persistAndFlush($entity);
        $entityId = $entity->getId();
        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->remove($entity);

        // Assert
        $this->assertEquals($initialCount - 1, $this->repository->count([]));
        $this->assertNull($this->repository->find($entityId));
    }

    public function testRemoveWithFlushFalseShouldNotImmediatelyDelete(): void
    {
        // Arrange
        $entity = $this->createTestLockConfiguration('/test/no-flush-remove');
        $this->persistAndFlush($entity);
        $entityId = $entity->getId();
        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->remove($entity, false);

        // Assert - 还未从数据库删除
        $this->assertEquals($initialCount, $this->repository->count([]));
        $this->assertNotNull($this->repository->find($entityId));

        // 手动刷新后才删除
        self::getEntityManager()->flush();
        $this->assertEquals($initialCount - 1, $this->repository->count([]));
        $this->assertNull($this->repository->find($entityId));
    }

    /**
     * 复杂查询测试
     */
    public function testFindByMultipleCriteriaShouldReturnMatchingEntities(): void
    {
        // 清理现有数据确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . LockConfiguration::class)->execute();
        $em->flush();

        // Arrange
        $entity1 = $this->createTestLockConfiguration('/admin/*', 60, true);
        $entity2 = $this->createTestLockConfiguration('/admin/*', 120, false);
        $entity3 = $this->createTestLockConfiguration('/user/*', 60, true);
        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);
        $this->persistAndFlush($entity3);

        // Act
        $result = $this->repository->findBy([
            'routePattern' => '/admin/*',
            'isEnabled' => true,
        ]);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('/admin/*', $result[0]->getRoutePattern());
        $this->assertTrue($result[0]->isEnabled());
        $this->assertEquals(60, $result[0]->getTimeoutSeconds());
    }

    public function testFindByWithOrderByAndLimitShouldReturnOrderedLimitedResults(): void
    {
        // Arrange
        $entity1 = $this->createTestLockConfiguration('/pattern1', 300);
        $entity2 = $this->createTestLockConfiguration('/pattern2', 60);
        $entity3 = $this->createTestLockConfiguration('/pattern3', 180);
        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);
        $this->persistAndFlush($entity3);

        // Act
        $result = $this->repository->findBy([], ['timeoutSeconds' => 'ASC'], 2);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals(60, $result[0]->getTimeoutSeconds()); // 最小值
        $this->assertEquals(180, $result[1]->getTimeoutSeconds()); // 第二小值
    }

    /**
     * 边界条件测试
     */
    public function testSaveWithMinimumTimeoutShouldSucceed(): void
    {
        // Arrange
        $entity = $this->createTestLockConfiguration('/test/min-timeout', 1);

        // Act
        $this->repository->save($entity);

        // Assert
        $this->assertNotNull($entity->getId());
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNotNull($savedEntity);
        $this->assertEquals(1, $savedEntity->getTimeoutSeconds());
    }

    public function testSaveWithMaximumTimeoutShouldSucceed(): void
    {
        // Arrange
        $entity = $this->createTestLockConfiguration('/test/max-timeout', 86400);

        // Act
        $this->repository->save($entity);

        // Assert
        $this->assertNotNull($entity->getId());
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNotNull($savedEntity);
        $this->assertEquals(86400, $savedEntity->getTimeoutSeconds());
    }

    public function testSaveWithLongRoutePatternShouldSucceed(): void
    {
        // Arrange
        $longPattern = '/' . str_repeat('a', 250); // 接近255字符限制
        $entity = $this->createTestLockConfiguration($longPattern);

        // Act
        $this->repository->save($entity);

        // Assert
        $this->assertNotNull($entity->getId());
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNotNull($savedEntity);
        $this->assertEquals($longPattern, $savedEntity->getRoutePattern());
    }

    public function testSaveWithLongDescriptionShouldSucceed(): void
    {
        // Arrange
        $longDescription = str_repeat('A', 495); // 接近500字符限制
        $entity = $this->createTestLockConfiguration('/test/long-desc', 60, true, $longDescription);

        // Act
        $this->repository->save($entity);

        // Assert
        $this->assertNotNull($entity->getId());
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNotNull($savedEntity);
        $this->assertEquals($longDescription, $savedEntity->getDescription());
    }

    /**
     * findBy 方法测试
     */

    /**
     * 数据库异常处理测试
     */
    public function testFindOneByWithEmptyCriteriaShouldReturnNull(): void
    {
        // 清理现有数据确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . LockConfiguration::class)->execute();
        $em->flush();

        // 由于没有数据，测试空条件查询应该返回 null
        $result = $this->repository->findOneBy([]);

        // 由于没有数据，应该返回 null
        $this->assertNull($result);
    }

    /**
     * 创建测试用的LockConfiguration实体
     */
    private function createTestLockConfiguration(
        string $routePattern = '/test/*',
        int $timeoutSeconds = 60,
        bool $isEnabled = true,
        ?string $description = null,
    ): LockConfiguration {
        $entity = new LockConfiguration();
        $entity->setRoutePattern($routePattern);
        $entity->setTimeoutSeconds($timeoutSeconds);
        $entity->setIsEnabled($isEnabled);
        $entity->setDescription($description);

        return $entity;
    }

    protected function createNewEntity(): object
    {
        $configuration = new LockConfiguration();
        $configuration->setRoutePattern('/test/pattern/' . uniqid());
        $configuration->setTimeoutSeconds(60);
        $configuration->setIsEnabled(true);
        $configuration->setDescription('测试配置描述');

        return $configuration;
    }
}
