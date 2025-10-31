<?php

namespace Tourze\IdleLockScreenBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;
use Tourze\IdleLockScreenBundle\Repository\LockConfigurationRepository;

/**
 * 无操作锁定检测服务
 * 负责检测路由是否需要锁定以及获取相关配置
 */
readonly class IdleLockDetector
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LockConfigurationRepository $lockConfigurationRepository,
        private RoutePatternMatcher $routePatternMatcher,
    ) {
    }

    /**
     * 检查指定路由是否需要锁定
     */
    public function shouldLockRoute(string $route): bool
    {
        $configuration = $this->getRouteConfiguration($route);

        return null !== $configuration;
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
     * @return LockConfiguration[]
     */
    public function getEnabledConfigurations(): array
    {
        $qb = $this->lockConfigurationRepository->createQueryBuilder('lc');
        $qb->where('lc.isEnabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('lc.routePattern', 'ASC')
        ;

        $result = $qb->getQuery()->getResult();

        /** @var LockConfiguration[] $result */
        return is_array($result) ? $result : [];
    }

    /**
     * 获取所有配置（包括禁用的）
     * @return LockConfiguration[]
     */
    public function getAllConfigurations(): array
    {
        $qb = $this->lockConfigurationRepository->createQueryBuilder('lc');
        $qb->orderBy('lc.createTime', 'DESC');

        $result = $qb->getQuery()->getResult();

        /** @var LockConfiguration[] $result */
        return is_array($result) ? $result : [];
    }

    /**
     * 创建新的锁定配置
     */
    public function createConfiguration(
        string $routePattern,
        int $timeoutSeconds = 60,
        bool $isEnabled = true,
        ?string $description = null,
    ): LockConfiguration {
        $config = new LockConfiguration();
        $config->setRoutePattern($routePattern);
        $config->setTimeoutSeconds($timeoutSeconds);
        $config->setIsEnabled($isEnabled);
        $config->setDescription($description);

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
        ?string $description = null,
    ): LockConfiguration {
        if (null !== $routePattern) {
            $config->setRoutePattern($routePattern);
        }
        if (null !== $timeoutSeconds) {
            $config->setTimeoutSeconds($timeoutSeconds);
        }
        if (null !== $isEnabled) {
            $config->setIsEnabled($isEnabled);
        }
        if (null !== $description) {
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
        return $this->lockConfigurationRepository->find($id);
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
     * @return LockConfiguration[]
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
     * @param int[] $configIds
     */
    public function toggleConfigurations(array $configIds, bool $enabled): int
    {
        return $this->lockConfigurationRepository->toggleConfigurations($configIds, $enabled);
    }
}
