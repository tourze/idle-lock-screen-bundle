<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Exception;

/**
 * 无效加载器异常
 * 当控制器加载器配置无效时抛出
 */
class InvalidLoaderException extends \InvalidArgumentException
{
    public function __construct(string $message = 'LoaderInterface is required', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
