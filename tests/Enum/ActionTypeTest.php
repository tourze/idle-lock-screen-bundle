<?php

namespace Tourze\IdleLockScreenBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\IdleLockScreenBundle\Enum\ActionType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ActionType::class)]
final class ActionTypeTest extends AbstractEnumTestCase
{
    public function testCasesContainsAllExpectedValues(): void
    {
        $expectedValues = ['locked', 'unlocked', 'timeout', 'bypass_attempt'];
        $actualValues = array_map(fn ($case) => $case->value, ActionType::cases());

        $this->assertEquals($expectedValues, $actualValues);
    }

    public function testGetLabelReturnsCorrectTranslations(): void
    {
        $this->assertEquals('已锁定', ActionType::LOCKED->getLabel());
        $this->assertEquals('已解锁', ActionType::UNLOCKED->getLabel());
        $this->assertEquals('超时', ActionType::TIMEOUT->getLabel());
        $this->assertEquals('绕过尝试', ActionType::BYPASS_ATTEMPT->getLabel());
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $actionType = ActionType::LOCKED;

        $this->assertInstanceOf(Labelable::class, $actionType);
        $this->assertInstanceOf(Itemable::class, $actionType);
        $this->assertInstanceOf(Selectable::class, $actionType);
    }

    public function testEachCaseHasUniqueValue(): void
    {
        $values = array_map(fn ($case) => $case->value, ActionType::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($values), $uniqueValues);
    }

    public function testEachCaseHasNonEmptyLabel(): void
    {
        foreach (ActionType::cases() as $case) {
            $label = $case->getLabel();

            $this->assertNotEmpty($label);
        }
    }

    public function testFromValueWorksCorrectly(): void
    {
        $this->assertEquals(ActionType::LOCKED, ActionType::from('locked'));
        $this->assertEquals(ActionType::UNLOCKED, ActionType::from('unlocked'));
        $this->assertEquals(ActionType::TIMEOUT, ActionType::from('timeout'));
        $this->assertEquals(ActionType::BYPASS_ATTEMPT, ActionType::from('bypass_attempt'));
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(ActionType::tryFrom('invalid_value'));
        $this->assertNull(ActionType::tryFrom(''));
    }

    public function testNamePropertyMatchesExpectedValues(): void
    {
        $this->assertEquals('LOCKED', ActionType::LOCKED->name);
        $this->assertEquals('UNLOCKED', ActionType::UNLOCKED->name);
        $this->assertEquals('TIMEOUT', ActionType::TIMEOUT->name);
        $this->assertEquals('BYPASS_ATTEMPT', ActionType::BYPASS_ATTEMPT->name);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = ActionType::LOCKED->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('locked', $result['value']);
        $this->assertEquals('已锁定', $result['label']);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $result = ActionType::LOCKED->toSelectItem();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('已锁定', $result['label']);
        $this->assertEquals('已锁定', $result['text']);
        $this->assertEquals('locked', $result['value']);
        $this->assertEquals('已锁定', $result['name']);
    }
}
