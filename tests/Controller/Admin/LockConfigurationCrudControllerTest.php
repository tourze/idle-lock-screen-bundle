<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\IdleLockScreenBundle\Controller\Admin\LockConfigurationCrudController;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * LockConfigurationCrudController 测试用例
 *
 * @internal
 */
#[CoversClass(LockConfigurationCrudController::class)]
#[RunTestsInSeparateProcesses]
final class LockConfigurationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): LockConfigurationCrudController
    {
        return new LockConfigurationCrudController();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '路由模式' => ['路由模式'];
        yield '超时时间（秒）' => ['超时时间（秒）'];
        yield '启用状态' => ['启用状态'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'routePattern' => ['routePattern'];
        yield 'timeoutSeconds' => ['timeoutSeconds'];
        yield 'isEnabled' => ['isEnabled'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'routePattern' => ['routePattern'];
        yield 'timeoutSeconds' => ['timeoutSeconds'];
        yield 'isEnabled' => ['isEnabled'];
    }

    public function testControllerHasAdminCrudAttribute(): void
    {
        $reflection = new \ReflectionClass(LockConfigurationCrudController::class);
        $attributes = $reflection->getAttributes();

        $hasAdminCrudAttribute = false;
        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            if ('EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud' === $attributeName) {
                $hasAdminCrudAttribute = true;
                $attributeInstance = $attribute->newInstance();
                $reflectionProperty = new \ReflectionProperty($attributeInstance, 'routePath');
                $reflectionProperty->setAccessible(true);
                $routePath = $reflectionProperty->getValue($attributeInstance);
                $this->assertEquals('/idle-lock/configuration', $routePath);
                break;
            }
        }

        $this->assertTrue($hasAdminCrudAttribute);
    }

    public function testControllerConfigureFilters(): void
    {
        $controller = new LockConfigurationCrudController();
        $filters = Filters::new();

        $result = $controller->configureFilters($filters);

        $this->assertInstanceOf(Filters::class, $result);
        $this->assertNotNull($result);
    }

    public function testControllerIsInstantiable(): void
    {
        $controller = new LockConfigurationCrudController();

        $this->assertNotNull($controller);
        $this->assertInstanceOf(LockConfigurationCrudController::class, $controller);
    }

    public function testControllerHasConfigureCrudMethod(): void
    {
        $controller = new LockConfigurationCrudController();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue($reflection->hasMethod('configureCrud'));
        $this->assertTrue($reflection->getMethod('configureCrud')->isPublic());
    }

    public function testControllerHasConfigureActionsMethod(): void
    {
        $controller = new LockConfigurationCrudController();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue($reflection->hasMethod('configureActions'));
        $this->assertTrue($reflection->getMethod('configureActions')->isPublic());
    }

    public function testControllerHasConfigureFieldsMethod(): void
    {
        $controller = new LockConfigurationCrudController();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue($reflection->hasMethod('configureFields'));
        $this->assertTrue($reflection->getMethod('configureFields')->isPublic());
    }

    public function testControllerMethodSignatures(): void
    {
        $controller = new LockConfigurationCrudController();
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

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问新建页面
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        // 获取表单并提交空数据 - 查找任何类型的提交按钮
        $button = $crawler->selectButton('Create');
        if (0 === $button->count()) {
            $button = $crawler->selectButton('保存');
        }
        if (0 === $button->count()) {
            $button = $crawler->selectButton('Submit');
        }

        $form = $button->form();
        $entityName = $this->getEntitySimpleName();

        // 清空必填字段
        $form[$entityName . '[routePattern]'] = '';
        $form[$entityName . '[timeoutSeconds]'] = '0';

        $crawler = $client->submit($form);

        // 验证返回422状态码表示验证失败
        $this->assertResponseStatusCodeSame(422);

        // 验证错误信息存在
        $errorText = $crawler->filter('.invalid-feedback')->text();
        $this->assertStringContainsString('不能为空', $errorText);
    }
}
