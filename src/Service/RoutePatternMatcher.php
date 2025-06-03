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
        // 检查是否包含正则表达式特殊字符，但排除通配符
        if (str_starts_with($pattern, '^') || str_ends_with($pattern, '$')) {
            return true;
        }

        // 检查是否包含正则表达式分组、字符类等
        if (preg_match('/[\\(\\)\\[\\]\\{\\}\\|\\+\\?]/', $pattern)) {
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
            if (!preg_match('/^[#\/].+[#\/][gimxs]*$/', $pattern)) {
                $pattern = '#' . $pattern . '#';
            }

            $result = @preg_match($pattern, $route);
            
            // 检查是否有错误
            if ($result === false || preg_last_error() !== PREG_NO_ERROR) {
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
     */
    private function matchParts(array $routeParts, array $patternParts): bool
    {
        $routeIndex = 0;
        $patternIndex = 0;
        
        while ($routeIndex < count($routeParts) && $patternIndex < count($patternParts)) {
            $routePart = $routeParts[$routeIndex];
            $patternPart = $patternParts[$patternIndex];
            
            if ($patternPart === '**') {
                // ** 匹配任意数量的段
                if ($patternIndex === count($patternParts) - 1) {
                    // ** 是最后一个模式，匹配剩余所有段
                    return true;
                }
                
                // 尝试匹配后续模式
                for ($i = $routeIndex; $i <= count($routeParts); $i++) {
                    if ($this->matchParts(
                        array_slice($routeParts, $i),
                        array_slice($patternParts, $patternIndex + 1)
                    )) {
                        return true;
                    }
                }
                return false;
            } elseif ($patternPart === '*') {
                // * 匹配单个段
                $routeIndex++;
                $patternIndex++;
            } else {
                // 精确匹配或正则匹配
                if (!$this->matchSinglePart($routePart, $patternPart)) {
                    return false;
                }
                $routeIndex++;
                $patternIndex++;
            }
        }
        
        // 检查是否都匹配完了
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
            $regex = str_replace('\\*', '.*', $regex);
            $regex = '#^' . $regex . '$#';
            return preg_match($regex, $routePart) === 1;
        }
        
        return false;
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
                // 检查是否已经是完整的正则表达式（以分隔符开头和结尾）
                if (!preg_match('/^[#\/].+[#\/][gimxs]*$/', $testPattern)) {
                    $testPattern = '#' . $testPattern . '#';
                }
                
                // 清除之前的错误
                preg_last_error();
                
                // 测试正则表达式
                $result = @preg_match($testPattern, '');
                $error = preg_last_error();
                
                // 如果有错误或者结果为 false，则无效
                if ($result === false || $error !== PREG_NO_ERROR) {
                    return false;
                }
                
                // 额外检查：检测常见的无效正则表达式模式
                // 检查未闭合的括号、方括号等
                $cleanPattern = trim($pattern, '^$');
                
                // 检查未闭合的方括号
                if (preg_match('/\[[^\]]*$/', $cleanPattern)) {
                    return false;
                }
                
                // 检查未闭合的圆括号
                $openParens = substr_count($cleanPattern, '(');
                $closeParens = substr_count($cleanPattern, ')');
                if ($openParens !== $closeParens) {
                    return false;
                }
                
                // 检查未闭合的花括号（量词）
                if (preg_match('/\{[^}]*$/', $cleanPattern)) {
                    return false;
                }
                
                // 检查孤立的花括号（不在量词位置）
                if (preg_match('/(?<![\w\]\)])[\{]/', $cleanPattern)) {
                    return false;
                }
                
                // 额外测试：尝试匹配不同的字符串
                @preg_match($testPattern, 'test');
                if (preg_last_error() !== PREG_NO_ERROR) {
                    return false;
                }
                
                @preg_match($testPattern, '/test/path');
                if (preg_last_error() !== PREG_NO_ERROR) {
                    return false;
                }
                
                return true;
            } catch (\Exception) {
                return false;
            }
        }

        // 对于通配符模式，检查是否有基本的语法错误
        if (str_contains($pattern, '*')) {
            // 通配符模式通常是有效的，除非有明显的语法错误
            return true;
        }

        // 精确匹配模式总是有效的
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
