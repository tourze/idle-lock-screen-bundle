<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Service;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

final readonly class IdleLockScreenRoutingAutoLoader implements RoutingAutoLoaderInterface
{
    public function __construct(
        #[Autowire(service: 'routing.loader.attribute.directory')]
        private LoaderInterface $directoryLoader,
    ) {
    }

    public function autoload(): RouteCollection
    {
        $controllerDir = __DIR__ . '/../Controller';

        if (!is_dir($controllerDir)) {
            return new RouteCollection();
        }

        // 使用属性目录加载器来自动发现控制器中的路由注解
        $result = $this->directoryLoader->load($controllerDir, 'attribute');

        return $result instanceof RouteCollection ? $result : new RouteCollection();
    }
}
