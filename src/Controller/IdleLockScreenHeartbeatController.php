<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\IdleLockScreenBundle\Service\LockManager;

final class IdleLockScreenHeartbeatController extends AbstractController
{
    public function __construct(
        private readonly LockManager $lockManager,
    ) {
    }

    #[Route(path: '/idle-lock/status', name: 'idle_lock_status', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'locked' => $this->lockManager->isSessionLocked(),
            'locked_route' => $this->lockManager->getLockedRoute(),
            'lock_time' => $this->lockManager->getLockTime(),
        ]);
    }
}
