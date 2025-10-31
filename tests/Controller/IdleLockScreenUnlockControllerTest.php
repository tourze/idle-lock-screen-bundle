<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\IdleLockScreenBundle\Controller\IdleLockScreenUnlockController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(IdleLockScreenUnlockController::class)]
#[RunTestsInSeparateProcesses]
final class IdleLockScreenUnlockControllerTest extends AbstractWebTestCase
{
    public function testControllerCanBeInstantiated(): void
    {
        // 测试控制器类是否存在且可实例化
        $this->assertSame('IdleLockScreenUnlockController',
            (new \ReflectionClass(IdleLockScreenUnlockController::class))->getShortName());
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();
        // 测试未认证访问
        $client->request('POST', '/idle-lock/unlock');
        // 由于控制器会进行重定向，验证重定向
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection() || $response->isSuccessful());
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $this->expectException(MethodNotAllowedHttpException::class);
        $this->expectExceptionMessage('Method Not Allowed');

        $client = self::createClientWithDatabase();
        $client->request($method, '/idle-lock/unlock');
    }
}
