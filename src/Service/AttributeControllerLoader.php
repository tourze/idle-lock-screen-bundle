<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Service;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;
use Tourze\IdleLockScreenBundle\Controller\IdleLockScreenHeartbeatController;
use Tourze\IdleLockScreenBundle\Controller\IdleLockScreenIndexController;
use Tourze\IdleLockScreenBundle\Controller\IdleLockScreenUnlockController;

final class AttributeControllerLoader implements LoaderInterface
{
    public function __construct(private readonly LoaderInterface $controllerLoader)
    {
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();

        $indexRoutes = $this->controllerLoader->load(IdleLockScreenIndexController::class);
        if ($indexRoutes instanceof RouteCollection) {
            $collection->addCollection($indexRoutes);
        }

        $unlockRoutes = $this->controllerLoader->load(IdleLockScreenUnlockController::class);
        if ($unlockRoutes instanceof RouteCollection) {
            $collection->addCollection($unlockRoutes);
        }

        $heartbeatRoutes = $this->controllerLoader->load(IdleLockScreenHeartbeatController::class);
        if ($heartbeatRoutes instanceof RouteCollection) {
            $collection->addCollection($heartbeatRoutes);
        }

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return is_string($resource) && str_ends_with($resource, 'Controller');
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->controllerLoader->getResolver();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->controllerLoader->setResolver($resolver);
    }
}
