<?php

namespace Tourze\IdleLockScreenBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 锁定操作类型枚举
 */
enum ActionType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case LOCKED = 'locked';
    case UNLOCKED = 'unlocked';
    case TIMEOUT = 'timeout';
    case BYPASS_ATTEMPT = 'bypass_attempt';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOCKED => '已锁定',
            self::UNLOCKED => '已解锁',
            self::TIMEOUT => '超时',
            self::BYPASS_ATTEMPT => '绕过尝试',
        };
    }
}
