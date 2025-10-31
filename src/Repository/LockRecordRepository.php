<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Enum\ActionType;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<LockRecord>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: LockRecord::class)]
class LockRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LockRecord::class);
    }

    public function save(LockRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LockRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据会话ID查找最新的记录
     */
    public function findLatestBySessionId(string $sessionId): ?LockRecord
    {
        $result = $this->createQueryBuilder('lr')
            ->andWhere('lr.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('lr.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof LockRecord ? $result : null;
    }

    /**
     * 查找指定用户的锁定记录
     *
     * @param mixed $user
     * @return LockRecord[]
     */
    public function findByUser(mixed $user): array
    {
        /** @var LockRecord[] $result */
        $result = $this->createQueryBuilder('lr')
            ->andWhere('lr.user = :user')
            ->setParameter('user', $user)
            ->orderBy('lr.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * 查找指定操作类型的记录
     *
     * @return LockRecord[]
     */
    public function findByActionType(ActionType $actionType): array
    {
        /** @var LockRecord[] $result */
        $result = $this->createQueryBuilder('lr')
            ->andWhere('lr.actionType = :actionType')
            ->setParameter('actionType', $actionType)
            ->orderBy('lr.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * 查找指定时间范围内的记录
     *
     * @return LockRecord[]
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var LockRecord[] $result */
        $result = $this->createQueryBuilder('lr')
            ->andWhere('lr.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lr.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }
}
