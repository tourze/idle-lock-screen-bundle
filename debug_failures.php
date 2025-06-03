<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Tourze\IdleLockScreenBundle\Service\RoutePatternMatcher;

$matcher = new RoutePatternMatcher();

// 测试失败的案例
$testCases = [
    // 第一个失败：精确匹配
    ['route' => '/billing/invoice/', 'pattern' => '/billing/invoice', 'expected' => false, 'description' => 'trailing slash should not match'],
    
    // 第二个失败：无效模式验证
    ['route' => '', 'pattern' => '/invalid{regex', 'expected' => false, 'description' => 'invalid regex should be invalid', 'test_type' => 'validation'],
    
    // 第三个失败：复杂通配符
    ['route' => '/path/document.pdf', 'pattern' => '/path/*.pdf', 'expected' => true, 'description' => 'file extension wildcard'],
];

foreach ($testCases as $case) {
    echo "Testing: {$case['description']}\n";
    echo "Route: {$case['route']}\n";
    echo "Pattern: {$case['pattern']}\n";
    echo "Pattern Type: " . $matcher->getPatternType($case['pattern']) . "\n";
    
    if (isset($case['test_type']) && $case['test_type'] === 'validation') {
        $result = $matcher->isValidPattern($case['pattern']);
        echo "Valid: " . ($result ? 'true' : 'false') . "\n";
    } else {
        $result = $matcher->matches($case['route'], $case['pattern']);
        echo "Matches: " . ($result ? 'true' : 'false') . "\n";
    }
    
    $status = $result === $case['expected'] ? '✓' : '✗';
    echo "Expected: " . ($case['expected'] ? 'true' : 'false') . "\n";
    echo "Status: $status\n";
    echo "---\n";
} 