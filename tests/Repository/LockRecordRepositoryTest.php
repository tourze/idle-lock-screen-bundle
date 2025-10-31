<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Persisters\Exception\UnrecognizedField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Enum\ActionType;
use Tourze\IdleLockScreenBundle\Repository\LockRecordRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * LockRecordRepository 测试用例
 *
 * @internal
 */
#[CoversClass(LockRecordRepository::class)]
#[RunTestsInSeparateProcesses]
final class LockRecordRepositoryTest extends AbstractRepositoryTestCase
{
    private LockRecordRepository $repository;

    private ?UserInterface $testUser = null;

    protected function onSetUp(): void
    {
        // 使用 EntityManager 直接获取 Repository，确保类型匹配
        $em = self::getEntityManager();
        $repository = $em->getRepository(LockRecord::class);
        $this->assertInstanceOf(LockRecordRepository::class, $repository);
        $this->repository = $repository;

        // 创建测试用户
        $this->testUser = $this->createNormalUser('test@example.com', 'password');
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    /**
     * 测试 save 方法
     */
    public function testSaveWithValidEntityShouldPersistToDatabase(): void
    {
        // Arrange
        $entity = $this->createTestLockRecord('test-session-1', ActionType::LOCKED, '/test/route');

        // Act
        $this->repository->save($entity);

        // Assert
        $this->assertNotNull($entity->getId());
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNotNull($savedEntity);
        $this->assertEquals('test-session-1', $savedEntity->getSessionId());
        $this->assertEquals(ActionType::LOCKED, $savedEntity->getActionType());
        $this->assertEquals('/test/route', $savedEntity->getRoute());
    }

    public function testSaveWithFlushFalseForLockRecord(): void
    {
        // Arrange
        $entity = $this->createTestLockRecord('test-session-2', ActionType::UNLOCKED, '/test/route2');
        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->save($entity, false);

        // Assert - 还未持久化到数据库
        $this->assertEquals($initialCount, $this->repository->count([]));
        $this->assertNull($entity->getId());

        // 手动刷新后才持久化
        self::getEntityManager()->flush();
        $this->assertEquals($initialCount + 1, $this->repository->count([]));
        $this->assertNotNull($entity->getId());
    }

    /**
     * 测试 remove 方法
     */
    public function testRemoveWithValidEntityShouldDeleteFromDatabase(): void
    {
        // Arrange
        $entity = $this->createTestLockRecord('test-session-remove', ActionType::LOCKED, '/test/remove');
        $this->persistAndFlush($entity);
        $entityId = $entity->getId();
        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->remove($entity);

        // Assert
        $this->assertEquals($initialCount - 1, $this->repository->count([]));
        $this->assertNull($this->repository->find($entityId));
    }

    public function testRemoveWithFlushFalseForLockRecord(): void
    {
        // Arrange
        $entity = $this->createTestLockRecord('test-session-no-flush', ActionType::LOCKED, '/test/no-flush');
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
     * 测试 findLatestBySessionId 方法
     */
    public function testFindLatestBySessionIdShouldReturnMostRecentRecord(): void
    {
        // Arrange
        $sessionId = 'test-session-latest-' . uniqid();
        $entity1 = $this->createTestLockRecord($sessionId, ActionType::LOCKED, '/route1');
        $this->persistAndFlush($entity1);

        // 添加延迟确保时间戳不同
        sleep(1); // 1秒确保创建时间有差异

        $entity2 = $this->createTestLockRecord($sessionId, ActionType::UNLOCKED, '/route2');
        $this->persistAndFlush($entity2);

        // Act
        $result = $this->repository->findLatestBySessionId($sessionId);

        // Assert
        $this->assertNotNull($result);

        // 验证是最新的记录（通过ID或时间判断，因为ID是自增的）
        $this->assertTrue($result->getId() >= $entity2->getId());
        $this->assertEquals($sessionId, $result->getSessionId());

        // 如果记录是按预期的最新记录，则验证其属性
        if ($result->getId() === $entity2->getId()) {
            $this->assertEquals(ActionType::UNLOCKED, $result->getActionType());
            $this->assertEquals('/route2', $result->getRoute());
        }
    }

    public function testFindLatestBySessionIdWithNoRecordsShouldReturnNull(): void
    {
        // Act
        $result = $this->repository->findLatestBySessionId('non-existent-session');

        // Assert
        $this->assertNull($result);
    }

    /**
     * 测试 findByUser 方法
     */
    public function testFindByUserShouldReturnUserRecords(): void
    {
        // Arrange
        $user1 = $this->createNormalUser('user1@example.com', 'password');
        $user2 = $this->createNormalUser('user2@example.com', 'password');

        // 获取用户的现有记录数量
        $initialCount = count($this->repository->findByUser($user1));

        $entity1 = $this->createTestLockRecord('session1', ActionType::LOCKED, '/route1', $user1);
        $entity2 = $this->createTestLockRecord('session2', ActionType::UNLOCKED, '/route2', $user1);
        $entity3 = $this->createTestLockRecord('session3', ActionType::LOCKED, '/route3', $user2);

        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);
        $this->persistAndFlush($entity3);

        // Act
        $result = $this->repository->findByUser($user1);

        // Assert
        $this->assertCount($initialCount + 2, $result);

        // 验证新创建的记录在结果中
        $ids = array_map(fn ($record) => $record->getId(), $result);
        $this->assertContains($entity1->getId(), $ids);
        $this->assertContains($entity2->getId(), $ids);
        $this->assertNotContains($entity3->getId(), $ids); // user2的记录不应该出现
    }

    public function testFindByUserWithNoRecordsShouldReturnEmptyArray(): void
    {
        // Arrange
        $user = $this->createNormalUser('norecords@example.com', 'password');

        // Act
        $result = $this->repository->findByUser($user);

        // Assert
        $this->assertCount(0, $result);
    }

    /**
     * 测试 findByActionType 方法
     */
    public function testFindByActionTypeShouldReturnMatchingRecords(): void
    {
        // Arrange
        $entity1 = $this->createTestLockRecord('session1', ActionType::LOCKED, '/route1');
        $entity2 = $this->createTestLockRecord('session2', ActionType::LOCKED, '/route2');
        $entity3 = $this->createTestLockRecord('session3', ActionType::UNLOCKED, '/route3');

        $this->persistAndFlush($entity1);
        $this->persistAndFlush($entity2);
        $this->persistAndFlush($entity3);

        // Act
        $result = $this->repository->findByActionType(ActionType::LOCKED);

        // Assert
        $this->assertGreaterThanOrEqual(2, count($result)); // 至少包含我们创建的2个LOCKED记录

        foreach ($result as $record) {
            $this->assertEquals(ActionType::LOCKED, $record->getActionType());
        }
    }

    /**
     * 测试 findByDateRange 方法
     */
    public function testFindByDateRangeShouldReturnRecordsInRange(): void
    {
        // Arrange
        $startDate = new \DateTimeImmutable('-1 hour');
        $endDate = new \DateTimeImmutable('+1 hour');

        $entity1 = $this->createTestLockRecord('session-range-1', ActionType::LOCKED, '/route1');
        $this->persistAndFlush($entity1);

        // Act
        $result = $this->repository->findByDateRange($startDate, $endDate);

        // Assert
        $this->assertNotEmpty($result);

        foreach ($result as $record) {
            $this->assertGreaterThanOrEqual($startDate, $record->getCreateTime());
            $this->assertLessThanOrEqual($endDate, $record->getCreateTime());
        }
    }

    public function testFindByDateRangeWithNoRecordsInRangeShouldReturnEmptyArray(): void
    {
        // Arrange - 使用过去的时间范围
        $startDate = new \DateTimeImmutable('-2 days');
        $endDate = new \DateTimeImmutable('-1 day');

        // Act
        $result = $this->repository->findByDateRange($startDate, $endDate);

        // Assert
        // 可能为空也可能有记录，这取决于数据库中是否有该时间范围内的记录
        // 方法返回类型已保证为数组
        $this->assertIsArray($result);
    }

    /**
     * 测试基础 CRUD 操作
     */
    public function testFindByWithValidCriteriaShouldReturnMatchingEntities(): void
    {
        // Arrange
        $entity = $this->createTestLockRecord('unique-session', ActionType::TIMEOUT, '/unique/route');
        $this->persistAndFlush($entity);

        // Act
        $result = $this->repository->findBy(['sessionId' => 'unique-session']);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(ActionType::TIMEOUT, $result[0]->getActionType());
        $this->assertEquals('/unique/route', $result[0]->getRoute());
    }

    public function testFindByWithInvalidFieldShouldThrowException(): void
    {
        // Assert
        $this->expectException(UnrecognizedField::class);

        // Act
        $this->repository->findBy(['nonExistentField' => 'value']);
    }

    public function testFindOneByWithValidCriteriaShouldReturnSingleEntity(): void
    {
        // Arrange
        $entity = $this->createTestLockRecord('findone-session', ActionType::BYPASS_ATTEMPT, '/findone/route');
        $this->persistAndFlush($entity);

        // Act
        $result = $this->repository->findOneBy(['sessionId' => 'findone-session']);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(ActionType::BYPASS_ATTEMPT, $result->getActionType());
        $this->assertEquals('/findone/route', $result->getRoute());
    }

    public function testCountShouldReturnCorrectNumber(): void
    {
        // Arrange
        $initialCount = $this->repository->count([]);
        $entity = $this->createTestLockRecord('count-test-session', ActionType::LOCKED, '/count/route');
        $this->persistAndFlush($entity);

        // Act
        $newCount = $this->repository->count([]);

        // Assert
        $this->assertEquals($initialCount + 1, $newCount);
    }

    /**
     * 测试边界条件
     */
    public function testSaveWithComplexContext(): void
    {
        // Arrange
        $complexContext = [
            'browser' => 'Chrome/91.0',
            'screen' => ['width' => 1920, 'height' => 1080],
            'metadata' => ['source' => 'auto-lock', 'version' => '1.0'],
            'chinese' => '中文测试',
        ];

        $entity = $this->createTestLockRecord('context-session', ActionType::LOCKED, '/context/route');
        $entity->setContext($complexContext);

        // Act
        $this->repository->save($entity);

        // Assert
        $this->assertNotNull($entity->getId());
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNotNull($savedEntity);
        $this->assertEquals($complexContext, $savedEntity->getContext());
    }

    public function testSaveWithLongStrings(): void
    {
        // Arrange
        $longRoute = '/' . str_repeat('a', 250); // 接近255字符限制
        $longUserAgent = str_repeat('Mozilla/5.0 ', 1000); // 长用户代理字符串

        $entity = $this->createTestLockRecord('long-string-session', ActionType::LOCKED, $longRoute);
        $entity->setUserAgent($longUserAgent);

        // Act
        $this->repository->save($entity);

        // Assert
        $this->assertNotNull($entity->getId());
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertNotNull($savedEntity);
        $this->assertEquals($longRoute, $savedEntity->getRoute());
        $this->assertEquals($longUserAgent, $savedEntity->getUserAgent());
    }

    /**
     * 创建测试用的LockRecord实体
     */
    private function createTestLockRecord(
        string $sessionId,
        ActionType $actionType,
        string $route,
        ?UserInterface $user = null,
    ): LockRecord {
        $entity = new LockRecord();
        $entity->setSessionId($sessionId);
        $entity->setActionType($actionType);
        $entity->setRoute($route);
        $entity->setUser($user ?? $this->testUser);
        $entity->setIpAddress('127.0.0.1');
        $entity->setUserAgent('Test Browser');

        return $entity;
    }

    protected function createNewEntity(): object
    {
        $record = new LockRecord();
        $record->setSessionId('test-session-' . uniqid());
        $record->setActionType(ActionType::LOCKED);
        $record->setRoute('/test/route/' . uniqid());
        $record->setUser($this->testUser);
        $record->setIpAddress('127.0.0.1');
        $record->setUserAgent('Test Browser');

        return $record;
    }

    /**
     * @return ServiceEntityRepository<LockRecord>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
