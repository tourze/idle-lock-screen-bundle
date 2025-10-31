<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('安全管理')) {
            $item->addChild('安全管理');
        }

        $securityMenu = $item->getChild('安全管理');

        if (null === $securityMenu) {
            return;
        }

        $securityMenu
            ->addChild('锁屏配置')
            ->setUri($this->linkGenerator->getCurdListPage(LockConfiguration::class))
            ->setAttribute('icon', 'fas fa-lock')
            ->setExtra('description', '管理无操作锁屏的路由配置和超时设置')
        ;

        $securityMenu
            ->addChild('锁屏记录')
            ->setUri($this->linkGenerator->getCurdListPage(LockRecord::class))
            ->setAttribute('icon', 'fas fa-history')
            ->setExtra('description', '查看用户锁定和解锁操作的历史记录')
        ;
    }
}
