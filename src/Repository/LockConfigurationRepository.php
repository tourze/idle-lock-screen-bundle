<?php

namespace Tourze\IdleLockScreenBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<LockConfiguration>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: LockConfiguration::class)]
class LockConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LockConfiguration::class);
    }

    public function save(LockConfiguration $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LockConfiguration $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 批量启用/禁用配置
     * @param int[] $configIds
     */
    public function toggleConfigurations(array $configIds, bool $enabled): int
    {
        $qb = $this->createQueryBuilder('lc');
        $qb->update()
            ->set('lc.isEnabled', ':enabled')
            ->set('lc.updateTime', ':updateTime')
            ->where('lc.id IN (:ids)')
            ->setParameter('enabled', $enabled)
            ->setParameter('updateTime', new \DateTimeImmutable())
            ->setParameter('ids', $configIds)
        ;

        $result = $qb->getQuery()->execute();

        return is_int($result) ? $result : 0;
    }
}
