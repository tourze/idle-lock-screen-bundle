<?php

namespace Tourze\IdleLockScreenBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;

class LockConfigurationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $configuration1 = new LockConfiguration();
        $configuration1->setRoutePattern('/admin/*');
        $configuration1->setTimeoutSeconds(300);
        $configuration1->setIsEnabled(true);
        $configuration1->setDescription('管理后台页面5分钟无操作锁定');

        $manager->persist($configuration1);

        $configuration2 = new LockConfiguration();
        $configuration2->setRoutePattern('/user/profile');
        $configuration2->setTimeoutSeconds(600);
        $configuration2->setIsEnabled(true);
        $configuration2->setDescription('用户资料页面10分钟无操作锁定');

        $manager->persist($configuration2);

        $configuration3 = new LockConfiguration();
        $configuration3->setRoutePattern('/api/sensitive/*');
        $configuration3->setTimeoutSeconds(180);
        $configuration3->setIsEnabled(false);
        $configuration3->setDescription('敏感API接口3分钟无操作锁定（已禁用）');

        $manager->persist($configuration3);

        $manager->flush();
    }
}
