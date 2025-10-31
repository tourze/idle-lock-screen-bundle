<?php

namespace Tourze\IdleLockScreenBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Enum\ActionType;

class LockRecordFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $record1 = new LockRecord();
        $record1->setSessionId('test_session_1');
        $record1->setActionType(ActionType::LOCKED);
        $record1->setRoute('/admin/dashboard');
        $record1->setIpAddress('192.168.1.100');
        $record1->setUserAgent('Mozilla/5.0 (Test Browser)');
        $record1->setContext(['reason' => 'timeout', 'duration' => 300]);

        $manager->persist($record1);

        $record2 = new LockRecord();
        $record2->setSessionId('test_session_2');
        $record2->setActionType(ActionType::UNLOCKED);
        $record2->setRoute('/user/profile');
        $record2->setIpAddress('10.0.0.50');
        $record2->setUserAgent('Mozilla/5.0 (Mobile Browser)');
        $record2->setContext(['unlock_method' => 'password']);

        $manager->persist($record2);

        $record3 = new LockRecord();
        $record3->setSessionId('test_session_3');
        $record3->setActionType(ActionType::TIMEOUT);
        $record3->setRoute('/api/data');
        $record3->setIpAddress('172.16.0.10');
        $record3->setContext(['timeout_duration' => 600]);

        $manager->persist($record3);

        $manager->flush();
    }
}
