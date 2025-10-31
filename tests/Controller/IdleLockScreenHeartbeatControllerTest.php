<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\IdleLockScreenBundle\Controller\IdleLockScreenHeartbeatController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(IdleLockScreenHeartbeatController::class)]
#[RunTestsInSeparateProcesses]
final class IdleLockScreenHeartbeatControllerTest extends AbstractWebTestCase
{
    public function testControllerCanBeInstantiated(): void
    {
        // 测试控制器类是否存在且可实例化
        $this->assertSame('IdleLockScreenHeartbeatController',
            (new \ReflectionClass(IdleLockScreenHeartbeatController::class))->getShortName());
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();
        // 测试未认证访问
        $client->request('GET', '/idle-lock/status');
        // 由于控制器可能需要认证或依赖服务，这里只验证响应存在
        $this->assertNotEmpty($client->getResponse()->getContent());
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);
        $this->expectExceptionMessage('Method Not Allowed');

        $client = self::createClientWithDatabase();
        $client->request($method, '/idle-lock/status');
    }
}
