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
     * @param string $route   要检查的路由
     * @param string $pattern 匹配模式
     *
     * @return bool 是否匹配
     */
    public function matches(string $route, string $pattern): bool
    {
        // 精确匹配 - 必须完全相同
        if ($route === $pattern) {
            return true;
        }

        // 正则表达式匹配（以^开头或包含特殊字符）
        if ($this->isRegexPattern($pattern)) {
            return $this->matchesRegex($route, $pattern);
        }

        // 通配符匹配
        if (str_contains($pattern, '*')) {
            return $this->matchesWildcard($route, $pattern);
        }

        // 如果没有通配符且不是正则表达式，则只能精确匹配
        return false;
    }

    /**
     * 检查多个模式中是否有匹配的
     *
     * @param string $route    要检查的路由
     * @param string[] $patterns 模式数组
     *
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
        // 检查是否包含正则表达式特殊字符，但排除通配符
        if (str_starts_with($pattern, '^') || str_ends_with($pattern, '$')) {
            return true;
        }

        // 检查是否包含正则表达式分组、字符类等
        if (1 === preg_match('/[\(\)\[\]\{\}\|\+\?]/', $pattern)) {
            return true;
        }

        return false;
    }

    /**
     * 正则表达式匹配
     */
    private function matchesRegex(string $route, string $pattern): bool
    {
        try {
            // 如果模式不是完整的正则表达式，添加分隔符
            // 检查是否已经是完整的正则表达式（以分隔符开头和结尾）
            if (1 !== preg_match('/^[#\/].+[#\/][gimxs]*$/', $pattern)) {
                $pattern = '#' . $pattern . '#';
            }

            $result = @preg_match($pattern, $route);

            // 检查是否有错误
            if (false === $result || PREG_NO_ERROR !== preg_last_error()) {
                // 如果正则表达式无效，回退到通配符匹配
                return $this->matchesWildcard($route, $pattern);
            }

            return (bool) $result;
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
        // 将路由和模式都分割成段来处理
        $routeParts = explode('/', trim($route, '/'));
        $patternParts = explode('/', trim($pattern, '/'));

        return $this->matchParts($routeParts, $patternParts);
    }

    /**
     * 递归匹配路径段
     * @param string[] $routeParts
     * @param string[] $patternParts
     */
    private function matchParts(array $routeParts, array $patternParts): bool
    {
        $routeIndex = 0;
        $patternIndex = 0;

        while ($this->hasRemainingParts($routeParts, $patternParts, $routeIndex, $patternIndex)) {
            $routePart = $routeParts[$routeIndex];
            $patternPart = $patternParts[$patternIndex];

            if ($this->isDoubleWildcard($patternPart)) {
                return $this->handleDoubleWildcard($routeParts, $patternParts, $routeIndex, $patternIndex);
            }

            if ($this->isSingleWildcard($patternPart)) {
                ++$routeIndex;
                ++$patternIndex;
            } else {
                if (!$this->matchSinglePart($routePart, $patternPart)) {
                    return false;
                }
                ++$routeIndex;
                ++$patternIndex;
            }
        }

        return $this->isCompleteMatch($routeParts, $patternParts, $routeIndex, $patternIndex);
    }

    /**
     * 检查是否还有剩余部分需要匹配
     */
    /**
     * @param string[] $routeParts
     * @param string[] $patternParts
     */
    private function hasRemainingParts(array $routeParts, array $patternParts, int $routeIndex, int $patternIndex): bool
    {
        return $routeIndex < count($routeParts) && $patternIndex < count($patternParts);
    }

    /**
     * 是否是双星通配符
     */
    private function isDoubleWildcard(string $patternPart): bool
    {
        return '**' === $patternPart;
    }

    /**
     * 是否是单星通配符
     */
    private function isSingleWildcard(string $patternPart): bool
    {
        return '*' === $patternPart;
    }

    /**
     * 处理双星通配符
     */
    /**
     * @param string[] $routeParts
     * @param string[] $patternParts
     */
    private function handleDoubleWildcard(array $routeParts, array $patternParts, int $routeIndex, int $patternIndex): bool
    {
        if ($this->isLastPattern($patternParts, $patternIndex)) {
            return true;
        }

        return $this->tryMatchRemaining($routeParts, $patternParts, $routeIndex, $patternIndex);
    }

    /**
     * 是否是最后一个模式
     */
    /**
     * @param string[] $patternParts
     */
    private function isLastPattern(array $patternParts, int $patternIndex): bool
    {
        return $patternIndex === count($patternParts) - 1;
    }

    /**
     * 尝试匹配剩余模式
     */
    /**
     * @param string[] $routeParts
     * @param string[] $patternParts
     */
    private function tryMatchRemaining(array $routeParts, array $patternParts, int $routeIndex, int $patternIndex): bool
    {
        for ($i = $routeIndex; $i <= count($routeParts); ++$i) {
            if ($this->matchParts(
                array_slice($routeParts, $i),
                array_slice($patternParts, $patternIndex + 1)
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否完全匹配
     */
    /**
     * @param string[] $routeParts
     * @param string[] $patternParts
     */
    private function isCompleteMatch(array $routeParts, array $patternParts, int $routeIndex, int $patternIndex): bool
    {
        return $routeIndex === count($routeParts) && $patternIndex === count($patternParts);
    }

    /**
     * 匹配单个路径段
     */
    private function matchSinglePart(string $routePart, string $patternPart): bool
    {
        if ($routePart === $patternPart) {
            return true;
        }

        // 如果包含通配符，转换为正则表达式
        if (str_contains($patternPart, '*')) {
            // 转义特殊字符，但保留通配符
            $regex = preg_quote($patternPart, '#');
            // 将转义的通配符转换为正则表达式
            $regex = str_replace('\*', '.*', $regex);
            $regex = '#^' . $regex . '$#';

            return 1 === preg_match($regex, $routePart);
        }

        return false;
    }

    /**
     * 验证模式是否有效
     */
    public function isValidPattern(string $pattern): bool
    {
        if ('' === $pattern) {
            return false;
        }

        if ($this->isRegexPattern($pattern)) {
            return $this->validateRegexPattern($pattern);
        }

        if (str_contains($pattern, '*')) {
            return $this->validateWildcardPattern($pattern);
        }

        // 精确匹配模式总是有效的
        return true;
    }

    /**
     * 验证正则表达式模式
     */
    private function validateRegexPattern(string $pattern): bool
    {
        try {
            $testPattern = $this->normalizeRegexPattern($pattern);

            if (!$this->testRegexCompilation($testPattern)) {
                return false;
            }

            return $this->validateRegexSyntax($pattern) && $this->testRegexMatching($testPattern);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * 验证通配符模式
     */
    private function validateWildcardPattern(string $pattern): bool
    {
        // 通配符模式通常是有效的，除非有明显的语法错误
        return true;
    }

    /**
     * 标准化正则表达式模式
     */
    private function normalizeRegexPattern(string $pattern): string
    {
        if (1 !== preg_match('/^[#\/].+[#\/][gimxs]*$/', $pattern)) {
            return '#' . $pattern . '#';
        }

        return $pattern;
    }

    /**
     * 测试正则表达式编译
     */
    private function testRegexCompilation(string $testPattern): bool
    {
        preg_last_error();
        $result = @preg_match($testPattern, '');
        $error = preg_last_error();

        return false !== $result && PREG_NO_ERROR === $error;
    }

    /**
     * 验证正则表达式语法
     */
    private function validateRegexSyntax(string $pattern): bool
    {
        $cleanPattern = trim($pattern, '^$');

        return $this->validateBrackets($cleanPattern)
            && $this->validateParentheses($cleanPattern)
            && $this->validateBraces($cleanPattern);
    }

    /**
     * 验证方括号
     */
    private function validateBrackets(string $pattern): bool
    {
        return 1 !== preg_match('/\[[^\]]*$/', $pattern);
    }

    /**
     * 验证圆括号
     */
    private function validateParentheses(string $pattern): bool
    {
        $openParens = substr_count($pattern, '(');
        $closeParens = substr_count($pattern, ')');

        return $openParens === $closeParens;
    }

    /**
     * 验证花括号
     */
    private function validateBraces(string $pattern): bool
    {
        if (1 === preg_match('/\{[^}]*$/', $pattern)) {
            return false;
        }

        return 1 !== preg_match('/(?<![\w\]\)])[\{]/', $pattern);
    }

    /**
     * 测试正则表达式匹配
     */
    private function testRegexMatching(string $testPattern): bool
    {
        $testStrings = ['test', '/test/path'];

        foreach ($testStrings as $testString) {
            @preg_match($testPattern, $testString);
            if (PREG_NO_ERROR !== preg_last_error()) {
                return false;
            }
        }

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
