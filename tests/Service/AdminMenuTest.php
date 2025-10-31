<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Service;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\IdleLockScreenBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * AdminMenu 测试用例
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    private function createAdminMenu(): AdminMenu
    {
        return self::getService(AdminMenu::class);
    }

    public function testInvokeCreatesSecurityManagementMenu(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        ($this->createAdminMenu())($rootMenu);

        $securityMenu = $rootMenu->getChild('安全管理');
        $this->assertNotNull($securityMenu);

        $lockConfigItem = $securityMenu->getChild('锁屏配置');
        $this->assertNotNull($lockConfigItem);
        $this->assertNotEmpty($lockConfigItem->getUri());
        $this->assertEquals('fas fa-lock', $lockConfigItem->getAttribute('icon'));
        $this->assertEquals('管理无操作锁屏的路由配置和超时设置', $lockConfigItem->getExtra('description'));

        $lockRecordItem = $securityMenu->getChild('锁屏记录');
        $this->assertNotNull($lockRecordItem);
        $this->assertNotEmpty($lockRecordItem->getUri());
        $this->assertEquals('fas fa-history', $lockRecordItem->getAttribute('icon'));
        $this->assertEquals('查看用户锁定和解锁操作的历史记录', $lockRecordItem->getExtra('description'));
    }

    public function testInvokeWithExistingSecurityManagementMenu(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);
        $existingSecurityMenu = new MenuItem('安全管理', $menuFactory);
        $rootMenu->addChild($existingSecurityMenu);

        ($this->createAdminMenu())($rootMenu);

        $securityMenu = $rootMenu->getChild('安全管理');
        $this->assertSame($existingSecurityMenu, $securityMenu);

        $this->assertNotNull($securityMenu->getChild('锁屏配置'));
        $this->assertNotNull($securityMenu->getChild('锁屏记录'));
    }

    public function testInvokeDoesNotCreateSecurityMenuIfNotNeeded(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        // 确认初始状态下没有安全管理菜单
        $this->assertNull($rootMenu->getChild('安全管理'));

        ($this->createAdminMenu())($rootMenu);

        // 调用后应该创建安全管理菜单
        $this->assertNotNull($rootMenu->getChild('安全管理'));
    }

    public function testLockConfigurationMenuItemProperties(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        ($this->createAdminMenu())($rootMenu);

        $securityMenu = $rootMenu->getChild('安全管理');
        $this->assertNotNull($securityMenu);
        $lockConfigItem = $securityMenu->getChild('锁屏配置');

        $this->assertNotNull($lockConfigItem);
        $this->assertEquals('锁屏配置', $lockConfigItem->getName());
        $lockConfigUri = $lockConfigItem->getUri();
        $this->assertNotNull($lockConfigUri);
        $this->assertStringContainsString('admin', $lockConfigUri);
        $this->assertStringContainsString('LockConfiguration', $lockConfigUri);
        $this->assertEquals('fas fa-lock', $lockConfigItem->getAttribute('icon'));
        $this->assertNotEmpty($lockConfigItem->getExtra('description'));
    }

    public function testLockRecordMenuItemProperties(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        ($this->createAdminMenu())($rootMenu);

        $securityMenu = $rootMenu->getChild('安全管理');
        $this->assertNotNull($securityMenu);
        $lockRecordItem = $securityMenu->getChild('锁屏记录');

        $this->assertNotNull($lockRecordItem);
        $this->assertEquals('锁屏记录', $lockRecordItem->getName());
        $lockRecordUri = $lockRecordItem->getUri();
        $this->assertNotNull($lockRecordUri);
        $this->assertStringContainsString('admin', $lockRecordUri);
        $this->assertStringContainsString('LockRecord', $lockRecordUri);
        $this->assertEquals('fas fa-history', $lockRecordItem->getAttribute('icon'));
        $this->assertNotEmpty($lockRecordItem->getExtra('description'));
    }

    public function testMultipleInvocationsShouldNotDuplicateMenuItems(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        $adminMenu = $this->createAdminMenu();

        // 第一次调用
        $adminMenu($rootMenu);
        $securityMenu = $rootMenu->getChild('安全管理');
        $this->assertNotNull($securityMenu);
        $initialChildCount = count($securityMenu->getChildren());

        // 第二次调用
        $adminMenu($rootMenu);
        $securityMenuAfter = $rootMenu->getChild('安全管理');
        $this->assertSame($securityMenu, $securityMenuAfter);
        $this->assertCount($initialChildCount, $securityMenuAfter->getChildren());

        // 验证菜单项没有重复
        $this->assertCount(1, array_filter($securityMenu->getChildren(), fn ($child) => '锁屏配置' === $child->getName()));
        $this->assertCount(1, array_filter($securityMenu->getChildren(), fn ($child) => '锁屏记录' === $child->getName()));
    }

    public function testMenuItemsHaveCorrectHierarchy(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        ($this->createAdminMenu())($rootMenu);

        // 验证层级结构：root -> 安全管理 -> 锁屏配置/锁屏记录
        $securityMenu = $rootMenu->getChild('安全管理');
        $this->assertNotNull($securityMenu);
        $this->assertSame($rootMenu, $securityMenu->getParent());

        $lockConfigItem = $securityMenu->getChild('锁屏配置');
        $this->assertNotNull($lockConfigItem);
        $this->assertSame($securityMenu, $lockConfigItem->getParent());

        $lockRecordItem = $securityMenu->getChild('锁屏记录');
        $this->assertNotNull($lockRecordItem);
        $this->assertSame($securityMenu, $lockRecordItem->getParent());
    }

    public function testMenuItemUrisAreGeneratedByLinkGenerator(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        ($this->createAdminMenu())($rootMenu);

        $securityMenu = $rootMenu->getChild('安全管理');
        $this->assertNotNull($securityMenu);
        $lockConfigItem = $securityMenu->getChild('锁屏配置');
        $lockRecordItem = $securityMenu->getChild('锁屏记录');
        $this->assertNotNull($lockConfigItem);
        $this->assertNotNull($lockRecordItem);

        // URI应该由LinkGenerator生成，包含CRUD列表页面路径
        $lockConfigUri = $lockConfigItem->getUri();
        $lockRecordUri = $lockRecordItem->getUri();
        $this->assertNotNull($lockConfigUri);
        $this->assertNotNull($lockRecordUri);
        $this->assertNotEmpty($lockConfigUri);
        $this->assertNotEmpty($lockRecordUri);

        // URI应该不同
        $this->assertNotEquals($lockConfigUri, $lockRecordUri);
    }

    public function testServiceImplementsMenuProviderInterface(): void
    {
        $adminMenu = $this->createAdminMenu();

        $this->assertInstanceOf('Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface', $adminMenu);
    }

    public function testServiceIsCallable(): void
    {
        $adminMenu = $this->createAdminMenu();

        $this->assertIsCallable($adminMenu);

        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        // 测试直接调用
        $adminMenu($rootMenu);

        // 验证菜单已被修改
        $this->assertNotNull($rootMenu->getChild('安全管理'));
    }
}
