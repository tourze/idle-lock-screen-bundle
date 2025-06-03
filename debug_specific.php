<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Tourze\IdleLockScreenBundle\Service\RoutePatternMatcher;

$matcher = new RoutePatternMatcher();

// 测试失败的案例
$testCases = [
    // 第一个失败：/billing/ 应该不匹配 /billing/*
    ['route' => '/billing/', 'pattern' => '/billing/*', 'expected' => false, 'description' => 'billing/ should not match billing/*'],
    
    // 第二个失败：正则表达式
    ['route' => '/billing/invoice', 'pattern' => '/billing/(invoice|payment)', 'expected' => true, 'description' => 'regex with groups'],
    
    // 第三个失败：复杂通配符
    ['route' => '/test/middle/end', 'pattern' => '*/middle/*', 'expected' => true, 'description' => 'wildcard at start'],
];

foreach ($testCases as $case) {
    echo "Testing: {$case['description']}\n";
    echo "Route: {$case['route']}\n";
    echo "Pattern: {$case['pattern']}\n";
    echo "Pattern Type: " . $matcher->getPatternType($case['pattern']) . "\n";
    
    $result = $matcher->matches($case['route'], $case['pattern']);
    $status = $result === $case['expected'] ? '✓' : '✗';
    
    echo "Expected: " . ($case['expected'] ? 'true' : 'false') . "\n";
    echo "Actual: " . ($result ? 'true' : 'false') . "\n";
    echo "Status: $status\n";
    echo "---\n";
} 