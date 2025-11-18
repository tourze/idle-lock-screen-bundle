<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\IdleLockScreenBundle\Controller\Admin\LockRecordCrudController;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * LockRecordCrudController 测试用例
 *
 * @internal
 */
#[CoversClass(LockRecordCrudController::class)]
#[RunTestsInSeparateProcesses]
final class LockRecordCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): LockRecordCrudController
    {
        return new LockRecordCrudController();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '用户' => ['用户'];
        yield '会话ID' => ['会话ID'];
        yield '操作类型' => ['操作类型'];
        yield '路由' => ['路由'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // 由于控制器禁用了EDIT操作，提供一个虚拟数据避免空数据集错误
        yield '虚拟字段' => ['虚拟字段'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // 由于控制器禁用了NEW操作，但需要至少提供一个数据避免空数据集错误
        yield '虚拟字段' => ['虚拟字段'];
    }

    public function testNewPageFieldsSkipped(): void
    {
        // 由于控制器禁用了NEW操作，跳过这个测试
        self::markTestSkipped('控制器禁用了NEW操作，跳过页面字段测试');
    }

    public function testEditPageFieldsSkipped(): void
    {
        // 由于控制器禁用了EDIT操作，跳过这个测试
        self::markTestSkipped('控制器禁用了EDIT操作，跳过字段提供器测试');
    }

    public function testControllerHasAdminCrudAttribute(): void
    {
        $reflection = new \ReflectionClass(LockRecordCrudController::class);
        $attributes = $reflection->getAttributes();

        $hasAdminCrudAttribute = false;
        foreach ($attributes as $attribute) {
            if ('EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud' === $attribute->getName()) {
                $hasAdminCrudAttribute = true;
                $attributeInstance = $attribute->newInstance();
                // 使用反射来访问属性，避免静态分析错误
                $reflection = new \ReflectionClass($attributeInstance);
                $property = $reflection->getProperty('routePath');
                $property->setAccessible(true);
                $this->assertEquals('/idle-lock/record', $property->getValue($attributeInstance));
                break;
            }
        }

        $this->assertTrue($hasAdminCrudAttribute);
    }

    public function testControllerConfigureFilters(): void
    {
        $controller = new LockRecordCrudController();
        $filters = Filters::new();

        $result = $controller->configureFilters($filters);

        $this->assertInstanceOf(Filters::class, $result);
        $this->assertNotNull($result);
    }

    public function testControllerIsInstantiable(): void
    {
        $controller = new LockRecordCrudController();

        $this->assertNotNull($controller);
        $this->assertInstanceOf(LockRecordCrudController::class, $controller);
    }

    public function testControllerHasConfigureCrudMethod(): void
    {
        $controller = new LockRecordCrudController();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue($reflection->hasMethod('configureCrud'));
        $this->assertTrue($reflection->getMethod('configureCrud')->isPublic());
    }

    public function testControllerHasConfigureActionsMethod(): void
    {
        $controller = new LockRecordCrudController();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue($reflection->hasMethod('configureActions'));
        $this->assertTrue($reflection->getMethod('configureActions')->isPublic());
    }

    public function testControllerHasConfigureFieldsMethod(): void
    {
        $controller = new LockRecordCrudController();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue($reflection->hasMethod('configureFields'));
        $this->assertTrue($reflection->getMethod('configureFields')->isPublic());
    }

    public function testControllerMethodSignatures(): void
    {
        $controller = new LockRecordCrudController();
        $reflection = new \ReflectionClass($controller);

        // 测试 configureCrud 方法签名
        $configureCrudMethod = $reflection->getMethod('configureCrud');
        $this->assertCount(1, $configureCrudMethod->getParameters());

        // 测试 configureActions 方法签名
        $configureActionsMethod = $reflection->getMethod('configureActions');
        $this->assertCount(1, $configureActionsMethod->getParameters());

        // 测试 configureFields 方法签名
        $configureFieldsMethod = $reflection->getMethod('configureFields');
        $this->assertCount(1, $configureFieldsMethod->getParameters());
    }
}
