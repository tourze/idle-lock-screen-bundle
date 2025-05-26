<?php

namespace Tourze\IdleLockScreenBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;

/**
 * 无操作锁定检测服务
 * 负责检测路由是否需要锁定以及获取相关配置
 */
class IdleLockDetector
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RoutePatternMatcher $routePatternMatcher
    ) {
    }

    /**
     * 检查指定路由是否需要锁定
     */
    public function shouldLockRoute(string $route): bool
    {
        $configuration = $this->getRouteConfiguration($route);
        return $configuration !== null;
    }

    /**
     * 获取路由的锁定配置
     */
    public function getRouteConfiguration(string $route): ?LockConfiguration
    {
        $configurations = $this->getEnabledConfigurations();

        foreach ($configurations as $config) {
            if ($this->routePatternMatcher->matches($route, $config->getRoutePattern())) {
                return $config;
            }
        }

        return null;
    }

    /**
     * 获取路由的超时时间（秒）
     */
    public function getRouteTimeout(string $route): int
    {
        $configuration = $this->getRouteConfiguration($route);
        return $configuration?->getTimeoutSeconds() ?? 60;
    }

    /**
     * 获取所有启用的锁定配置
     */
    public function getEnabledConfigurations(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('lc')
           ->from(LockConfiguration::class, 'lc')
           ->where('lc.isEnabled = :enabled')
           ->setParameter('enabled', true)
           ->orderBy('lc.routePattern', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * 获取所有配置（包括禁用的）
     */
    public function getAllConfigurations(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('lc')
           ->from(LockConfiguration::class, 'lc')
           ->orderBy('lc.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * 创建新的锁定配置
     */
    public function createConfiguration(
        string $routePattern,
        int $timeoutSeconds = 60,
        bool $isEnabled = true,
        ?string $description = null
    ): LockConfiguration {
        $config = new LockConfiguration();
        $config->setRoutePattern($routePattern)
               ->setTimeoutSeconds($timeoutSeconds)
               ->setIsEnabled($isEnabled)
               ->setDescription($description);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $config;
    }

    /**
     * 更新锁定配置
     */
    public function updateConfiguration(
        LockConfiguration $config,
        ?string $routePattern = null,
        ?int $timeoutSeconds = null,
        ?bool $isEnabled = null,
        ?string $description = null
    ): LockConfiguration {
        if ($routePattern !== null) {
            $config->setRoutePattern($routePattern);
        }
        if ($timeoutSeconds !== null) {
            $config->setTimeoutSeconds($timeoutSeconds);
        }
        if ($isEnabled !== null) {
            $config->setIsEnabled($isEnabled);
        }
        if ($description !== null) {
            $config->setDescription($description);
        }

        $this->entityManager->flush();

        return $config;
    }

    /**
     * 删除锁定配置
     */
    public function deleteConfiguration(LockConfiguration $config): void
    {
        $this->entityManager->remove($config);
        $this->entityManager->flush();
    }

    /**
     * 根据ID获取配置
     */
    public function getConfigurationById(int $id): ?LockConfiguration
    {
        return $this->entityManager->getRepository(LockConfiguration::class)->find($id);
    }

    /**
     * 验证路由模式是否有效
     */
    public function validateRoutePattern(string $pattern): bool
    {
        return $this->routePatternMatcher->isValidPattern($pattern);
    }

    /**
     * 获取匹配指定路由的所有配置
     */
    public function getMatchingConfigurations(string $route): array
    {
        $configurations = $this->getEnabledConfigurations();
        $matching = [];

        foreach ($configurations as $config) {
            if ($this->routePatternMatcher->matches($route, $config->getRoutePattern())) {
                $matching[] = $config;
            }
        }

        return $matching;
    }

    /**
     * 批量启用/禁用配置
     */
    public function toggleConfigurations(array $configIds, bool $enabled): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(LockConfiguration::class, 'lc')
           ->set('lc.isEnabled', ':enabled')
           ->set('lc.updatedAt', ':updatedAt')
           ->where('lc.id IN (:ids)')
           ->setParameter('enabled', $enabled)
           ->setParameter('updatedAt', new \DateTimeImmutable())
           ->setParameter('ids', $configIds);

        return $qb->getQuery()->execute();
    }
}
