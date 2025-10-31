<?php

namespace Tourze\IdleLockScreenBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class IdleLockScreenExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
