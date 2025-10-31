<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\IdleLockScreenBundle\IdleLockScreenBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(IdleLockScreenBundle::class)]
#[RunTestsInSeparateProcesses]
final class IdleLockScreenBundleTest extends AbstractBundleTestCase
{
}
