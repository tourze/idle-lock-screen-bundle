<?php

$route = '/test/middle/end';
$pattern = '*/middle/*';

echo "Original pattern: $pattern\n";

// 首先处理 ** 通配符
$pattern = str_replace('**', '___DOUBLE_WILDCARD___', $pattern);
echo "After ** replacement: $pattern\n";

// 然后转义所有正则表达式特殊字符
$pattern = preg_quote($pattern, '#');
echo "After preg_quote: $pattern\n";

// 恢复并转换通配符
$pattern = str_replace('___DOUBLE_WILDCARD___', '.*', $pattern);
echo "After ** restoration: $pattern\n";

// 处理单个通配符，区分开头、中间和结尾的情况
if (str_starts_with($pattern, '\\*')) {
    // 如果模式以 * 开头，允许匹配任何路径段
    $pattern = '[^/]*' . substr($pattern, 2);
    echo "After start wildcard: $pattern\n";
}
$pattern = str_replace('\\*', '[^/]+', $pattern);
echo "After other wildcards: $pattern\n";

// 添加边界匹配
$pattern = '#^' . $pattern . '$#';
echo "Final regex: $pattern\n";

$result = preg_match($pattern, $route);
echo "Match result: " . ($result ? 'true' : 'false') . "\n";

// 让我们看看这个路由应该如何匹配
echo "\nRoute analysis:\n";
echo "Route: $route\n";
echo "Should match: [^/]*/middle/[^/]+\n";
echo "Route parts: " . implode(' | ', explode('/', $route)) . "\n"; 