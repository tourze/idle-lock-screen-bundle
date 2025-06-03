<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Tourze\IdleLockScreenBundle\Service\RoutePatternMatcher;

$matcher = new RoutePatternMatcher();

// 测试单个通配符匹配
echo "测试单个通配符匹配:\n";
$route = '/billing/invoice';
$pattern = '/billing/*';

echo "Route: $route\n";
echo "Pattern: $pattern\n";
echo "Pattern Type: " . $matcher->getPatternType($pattern) . "\n";

// 手动测试通配符转换
$testPattern = str_replace('**', '___DOUBLE_WILDCARD___', $pattern);
echo "After ** replacement: $testPattern\n";

$testPattern = preg_quote($testPattern, '/');
echo "After preg_quote: $testPattern\n";

$testPattern = str_replace('___DOUBLE_WILDCARD___', '.*', $testPattern);
$testPattern = str_replace('\\*', '[^/]*', $testPattern);
echo "After wildcard conversion: $testPattern\n";

$testPattern = '/^' . $testPattern . '$/';
echo "Final regex: $testPattern\n";

$result = preg_match($testPattern, $route);
echo "preg_match result: " . ($result ? 'true' : 'false') . "\n";
echo "preg_last_error: " . preg_last_error() . "\n";

echo "\nActual matcher result: " . ($matcher->matches($route, $pattern) ? 'true' : 'false') . "\n";

// 测试正则表达式
echo "\n\n测试正则表达式匹配:\n";
$route = '/admin/users';
$pattern = '^/admin/.*';

echo "Route: $route\n";
echo "Pattern: $pattern\n";
echo "Pattern Type: " . $matcher->getPatternType($pattern) . "\n";

$testPattern = '/' . $pattern . '/';
echo "Final regex: $testPattern\n";

$result = preg_match($testPattern, $route);
echo "preg_match result: " . ($result ? 'true' : 'false') . "\n";
echo "preg_last_error: " . preg_last_error() . "\n";

echo "Actual matcher result: " . ($matcher->matches($route, $pattern) ? 'true' : 'false') . "\n";

// 测试模式类型识别
echo "\n测试模式类型识别:\n";
$patterns = ['/billing/*', '^/admin/.*', '/exact/path', '/billing/(invoice|payment)'];
foreach ($patterns as $pattern) {
    $type = $matcher->getPatternType($pattern);
    echo sprintf("Pattern: %s, Type: %s\n", $pattern, $type);
}

// 测试模式验证
echo "\n测试模式验证:\n";
$validationCases = ['^/admin/.*', '/billing/*', '', '/unclosed[bracket'];
foreach ($validationCases as $pattern) {
    $isValid = $matcher->isValidPattern($pattern);
    echo sprintf("Pattern: '%s', Valid: %s\n", $pattern, $isValid ? 'true' : 'false');
} 