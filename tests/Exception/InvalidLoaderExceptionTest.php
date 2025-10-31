<?php

namespace Tourze\IdleLockScreenBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\IdleLockScreenBundle\Exception\InvalidLoaderException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidLoaderException::class)]
final class InvalidLoaderExceptionTest extends AbstractExceptionTestCase
{
    protected function createException(string $message = '', int $code = 0, ?\Throwable $previous = null): \Throwable
    {
        return new InvalidLoaderException($message, $code, $previous);
    }

    protected function getDefaultMessage(): string
    {
        return 'LoaderInterface is required';
    }

    protected function getExpectedParentExceptionClass(): string
    {
        return \InvalidArgumentException::class;
    }

    public function testConstructorWithDefaultMessage(): void
    {
        $exception = new InvalidLoaderException();

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('LoaderInterface is required', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testConstructorWithCustomMessage(): void
    {
        $customMessage = 'Custom error message';
        $exception = new InvalidLoaderException($customMessage);

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExtendsInvalidArgumentException(): void
    {
        $exception = new InvalidLoaderException();

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testThrowableBehavior(): void
    {
        $this->expectException(InvalidLoaderException::class);
        $this->expectExceptionMessage('LoaderInterface is required');

        throw new InvalidLoaderException();
    }

    public function testThrowableWithCustomMessage(): void
    {
        $customMessage = 'Loader configuration is invalid';

        $this->expectException(InvalidLoaderException::class);
        $this->expectExceptionMessage($customMessage);

        throw new InvalidLoaderException($customMessage);
    }

    public function testExceptionCanBeCaughtAsInvalidArgumentException(): void
    {
        $caughtAsInvalidArgument = false;

        try {
            throw new InvalidLoaderException();
        } catch (\InvalidArgumentException $e) {
            $caughtAsInvalidArgument = true;
        }

        $this->assertTrue($caughtAsInvalidArgument);
    }

    public function testGetMessageReturnsCorrectMessage(): void
    {
        $messages = [
            'LoaderInterface is required',
            'Invalid loader configuration',
            'Loader must implement LoaderInterface',
            '',
        ];

        foreach ($messages as $message) {
            $exception = new InvalidLoaderException($message);
            $this->assertEquals($message, $exception->getMessage());
        }
    }
}
