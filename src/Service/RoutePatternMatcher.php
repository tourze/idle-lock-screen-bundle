<?php

namespace Tourze\IdleLockScreenBundle\Service;

/**
 * 路由模式匹配器
 * 支持通配符和正则表达式匹配路由
 */
class RoutePatternMatcher
{
    /**
     * 检查路由是否匹配指定的模式
     *
     * @param string $route 要检查的路由
     * @param string $pattern 匹配模式
     * @return bool 是否匹配
     */
    public function matches(string $route, string $pattern): bool
    {
        // 精确匹配
        if ($route === $pattern) {
            return true;
        }

        // 正则表达式匹配（以^开头或包含特殊字符）
        if ($this->isRegexPattern($pattern)) {
            return $this->matchesRegex($route, $pattern);
        }

        // 通配符匹配
        return $this->matchesWildcard($route, $pattern);
    }

    /**
     * 检查多个模式中是否有匹配的
     *
     * @param string $route 要检查的路由
     * @param array $patterns 模式数组
     * @return bool 是否有匹配的模式
     */
    public function matchesAny(string $route, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->matches($route, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断是否为正则表达式模式
     */
    private function isRegexPattern(string $pattern): bool
    {
        // 以^开头或包含正则表达式特殊字符
        return str_starts_with($pattern, '^') ||
               str_contains($pattern, '(') ||
               str_contains($pattern, '[') ||
               str_contains($pattern, '{') ||
               str_contains($pattern, '|') ||
               str_contains($pattern, '+') ||
               str_contains($pattern, '?') ||
               str_contains($pattern, '$');
    }

    /**
     * 正则表达式匹配
     */
    private function matchesRegex(string $route, string $pattern): bool
    {
        try {
            // 确保模式是有效的正则表达式
            if (!str_starts_with($pattern, '/')) {
                $pattern = '/' . $pattern . '/';
            }

            return (bool) preg_match($pattern, $route);
        } catch (\Exception) {
            // 如果正则表达式无效，回退到通配符匹配
            return $this->matchesWildcard($route, $pattern);
        }
    }

    /**
     * 通配符匹配
     * 支持 * 和 ** 通配符
     * * 匹配单个路径段中的任意字符（不包括/）
     * ** 匹配任意路径段（包括/）
     */
    private function matchesWildcard(string $route, string $pattern): bool
    {
        // 转义特殊字符，但保留通配符
        $escapedPattern = preg_quote($pattern, '/');
        
        // 将转义的通配符还原并转换为正则表达式
        $regexPattern = str_replace(
            ['\*\*', '\*'],
            ['.*', '[^/]*'],
            $escapedPattern
        );

        // 添加边界匹配
        $regexPattern = '/^' . $regexPattern . '$/';

        try {
            return (bool) preg_match($regexPattern, $route);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * 验证模式是否有效
     */
    public function isValidPattern(string $pattern): bool
    {
        if (empty($pattern)) {
            return false;
        }

        // 如果是正则表达式，验证其有效性
        if ($this->isRegexPattern($pattern)) {
            try {
                $testPattern = $pattern;
                if (!str_starts_with($testPattern, '/')) {
                    $testPattern = '/' . $testPattern . '/';
                }
                preg_match($testPattern, '');
                return preg_last_error() === PREG_NO_ERROR;
            } catch (\Exception) {
                return false;
            }
        }

        // 通配符模式总是有效的
        return true;
    }

    /**
     * 获取模式类型描述
     */
    public function getPatternType(string $pattern): string
    {
        if ($this->isRegexPattern($pattern)) {
            return 'regex';
        }

        if (str_contains($pattern, '*')) {
            return 'wildcard';
        }

        return 'exact';
    }
}
