<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\IdleLockScreenBundle\Service\RoutePatternMatcher;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * RoutePatternMatcher 集成测试
 *
 * @internal
 */
#[CoversClass(RoutePatternMatcher::class)]
#[RunTestsInSeparateProcesses]
final class RoutePatternMatcherTest extends AbstractIntegrationTestCase
{
    private RoutePatternMatcher $matcher;

    protected function onSetUp(): void
    {
        $this->matcher = self::getService(RoutePatternMatcher::class);
    }

    /**
     * 测试精确匹配
     */
    public function testMatchesExactMatch(): void
    {
        $this->assertTrue($this->matcher->matches('/billing/invoice', '/billing/invoice'));
        $this->assertTrue($this->matcher->matches('/', '/'));
        $this->assertTrue($this->matcher->matches('/admin', '/admin'));

        $this->assertFalse($this->matcher->matches('/billing/invoice', '/billing/payment'));
        $this->assertFalse($this->matcher->matches('/billing/invoice/', '/billing/invoice'));
        $this->assertFalse($this->matcher->matches('/Billing/Invoice', '/billing/invoice'));
    }

    /**
     * 测试单级通配符匹配
     */
    public function testMatchesSingleLevelWildcard(): void
    {
        $pattern = '/billing/*';

        // 应该匹配
        $this->assertTrue($this->matcher->matches('/billing/invoice', $pattern));
        $this->assertTrue($this->matcher->matches('/billing/payment', $pattern));
        $this->assertTrue($this->matcher->matches('/billing/123', $pattern));
        $this->assertTrue($this->matcher->matches('/billing/test-file', $pattern));
        $this->assertTrue($this->matcher->matches('/billing/file.pdf', $pattern));

        // 不应该匹配
        $this->assertFalse($this->matcher->matches('/billing', $pattern));
        $this->assertFalse($this->matcher->matches('/billing/', $pattern));
        $this->assertFalse($this->matcher->matches('/billing/sub/path', $pattern));
        $this->assertFalse($this->matcher->matches('/account/invoice', $pattern));
    }

    /**
     * 测试多级通配符匹配
     */
    public function testMatchesMultiLevelWildcard(): void
    {
        $pattern = '/billing/**';

        // 应该匹配
        $this->assertTrue($this->matcher->matches('/billing/invoice', $pattern));
        $this->assertTrue($this->matcher->matches('/billing/sub/path', $pattern));
        $this->assertTrue($this->matcher->matches('/billing/deep/nested/path', $pattern));
        $this->assertTrue($this->matcher->matches('/billing/file.pdf', $pattern));
        $this->assertTrue($this->matcher->matches('/billing/path/with/many/segments', $pattern));

        // 不应该匹配
        $this->assertFalse($this->matcher->matches('/billing', $pattern));
        $this->assertFalse($this->matcher->matches('/account/invoice', $pattern));
        $this->assertFalse($this->matcher->matches('/bills/invoice', $pattern));
    }

    /**
     * 测试正则表达式匹配
     */
    public function testMatchesRegexPattern(): void
    {
        // 以^开头的正则表达式
        $this->assertTrue($this->matcher->matches('/admin/users', '^/admin/.*'));
        $this->assertTrue($this->matcher->matches('/admin/settings', '^/admin/.*'));
        $this->assertTrue($this->matcher->matches('/admin/', '^/admin/.*'));
        $this->assertFalse($this->matcher->matches('/user/admin', '^/admin/.*'));

        // 复杂正则表达式
        $this->assertTrue($this->matcher->matches('/billing/invoice', '/billing/(invoice|payment)'));
        $this->assertTrue($this->matcher->matches('/billing/payment', '/billing/(invoice|payment)'));
        $this->assertFalse($this->matcher->matches('/billing/report', '/billing/(invoice|payment)'));

        // 数字匹配
        $this->assertTrue($this->matcher->matches('/api/v1', '/api/v[0-9]+'));
        $this->assertTrue($this->matcher->matches('/api/v123', '/api/v[0-9]+'));
        $this->assertFalse($this->matcher->matches('/api/vTest', '/api/v[0-9]+'));
    }

    /**
     * 测试特殊字符处理
     */
    public function testMatchesSpecialCharacters(): void
    {
        // 点号
        $this->assertTrue($this->matcher->matches('/file.pdf', '/file.pdf'));
        $this->assertFalse($this->matcher->matches('/fileXpdf', '/file.pdf'));

        // 括号
        $this->assertTrue($this->matcher->matches('/path(test)', '/path(test)'));

        // 问号
        $this->assertTrue($this->matcher->matches('/search?q=test', '/search?q=test'));

        // 加号
        $this->assertTrue($this->matcher->matches('/path+test', '/path+test'));
    }

    /**
     * 测试中文和特殊字符路由
     */
    public function testMatchesUnicodeCharacters(): void
    {
        $this->assertTrue($this->matcher->matches('/用户/账单', '/用户/账单'));
        $this->assertTrue($this->matcher->matches('/用户/账单', '/用户/*'));
        $this->assertTrue($this->matcher->matches('/用户/账单/详情', '/用户/**'));

        $this->assertTrue($this->matcher->matches('/path with spaces', '/path with spaces'));
        $this->assertTrue($this->matcher->matches('/path-with-dashes', '/path-with-dashes'));
    }

    /**
     * 测试空和无效输入
     */
    public function testMatchesEmptyAndInvalidInputs(): void
    {
        $this->assertTrue($this->matcher->matches('', ''));
        $this->assertFalse($this->matcher->matches('/path', ''));
        $this->assertFalse($this->matcher->matches('', '/path'));
    }

    /**
     * 测试 matchesAny 方法
     */
    public function testMatchesAnyWithMultiplePatterns(): void
    {
        $patterns = [
            '/billing/*',
            '/account/*',
            '^/admin/.*',
        ];

        $this->assertTrue($this->matcher->matchesAny('/billing/invoice', $patterns));
        $this->assertTrue($this->matcher->matchesAny('/account/settings', $patterns));
        $this->assertTrue($this->matcher->matchesAny('/admin/users', $patterns));

        $this->assertFalse($this->matcher->matchesAny('/public/home', $patterns));
        $this->assertFalse($this->matcher->matchesAny('/user/profile', $patterns));
    }

    /**
     * 测试 matchesAny 方法边界情况
     */
    public function testMatchesAnyEdgeCases(): void
    {
        // 空模式数组
        $this->assertFalse($this->matcher->matchesAny('/any/path', []));

        // 单个模式
        $this->assertTrue($this->matcher->matchesAny('/billing/invoice', ['/billing/*']));
        $this->assertFalse($this->matcher->matchesAny('/account/settings', ['/billing/*']));
    }

    /**
     * 测试模式验证
     */
    public function testIsValidPatternValidPatterns(): void
    {
        $validPatterns = [
            '/billing/*',
            '/billing/**',
            '^/admin/.*',
            '/billing/(invoice|payment)',
            '/exact/path',
            '/api/v[0-9]+',
            '/',
            '/path/with/中文',
        ];

        foreach ($validPatterns as $pattern) {
            $this->assertTrue($this->matcher->isValidPattern($pattern), "Pattern should be valid: {$pattern}");
        }
    }

    /**
     * 测试无效模式
     */
    public function testIsValidPatternInvalidPatterns(): void
    {
        $invalidPatterns = [
            '',                    // 空字符串
            '/unclosed[bracket',   // 未闭合括号
            '/invalid{regex',      // 无效正则
            '/bad(group',          // 未闭合分组
        ];

        foreach ($invalidPatterns as $pattern) {
            $this->assertFalse($this->matcher->isValidPattern($pattern), "Pattern should be invalid: {$pattern}");
        }
    }

    /**
     * 测试获取模式类型
     */
    public function testGetPatternType(): void
    {
        $this->assertEquals('exact', $this->matcher->getPatternType('/billing/invoice'));
        $this->assertEquals('wildcard', $this->matcher->getPatternType('/billing/*'));
        $this->assertEquals('wildcard', $this->matcher->getPatternType('/billing/**'));
        $this->assertEquals('regex', $this->matcher->getPatternType('^/admin/.*'));
        $this->assertEquals('regex', $this->matcher->getPatternType('/billing/(invoice|payment)'));
        $this->assertEquals('regex', $this->matcher->getPatternType('/api/v[0-9]+'));
    }

    /**
     * 测试复杂的通配符场景
     */
    public function testMatchesComplexWildcardScenarios(): void
    {
        // 多个通配符
        $this->assertTrue($this->matcher->matches('/a/b/c/d', '/*/b/*/d'));
        $this->assertFalse($this->matcher->matches('/a/b/c/e', '/*/b/*/d'));

        // 通配符在不同位置
        $this->assertTrue($this->matcher->matches('/prefix/test/suffix', '/prefix/*/suffix'));
        $this->assertTrue($this->matcher->matches('/test/middle/end', '*/middle/*'));

        // 文件扩展名通配符
        $this->assertTrue($this->matcher->matches('/path/file.pdf', '/path/*.pdf'));
        $this->assertTrue($this->matcher->matches('/path/document.pdf', '/path/*.pdf'));
        $this->assertFalse($this->matcher->matches('/path/file.doc', '/path/*.pdf'));
    }

    /**
     * 测试正则表达式边界情况
     */
    public function testMatchesRegexEdgeCases(): void
    {
        // 无效正则表达式应该回退到通配符匹配
        $invalidRegex = '/billing/[unclosed';
        $this->assertFalse($this->matcher->matches('/billing/invoice', $invalidRegex));

        // 复杂但有效的正则
        $complexRegex = '^/api/v[1-9][0-9]*/users/[0-9]+$';
        $this->assertTrue($this->matcher->matches('/api/v1/users/123', $complexRegex));
        $this->assertTrue($this->matcher->matches('/api/v10/users/456', $complexRegex));
        $this->assertFalse($this->matcher->matches('/api/v0/users/123', $complexRegex));
        $this->assertFalse($this->matcher->matches('/api/v1/users/abc', $complexRegex));
    }

    /**
     * 测试性能相关场景
     */
    public function testMatchesPerformanceScenarios(): void
    {
        $longPath = '/' . str_repeat('segment/', 100) . 'end';
        $longPattern = '/' . str_repeat('segment/', 100) . 'end';

        $this->assertTrue($this->matcher->matches($longPath, $longPattern));
        $this->assertTrue($this->matcher->matches($longPath, '/**'));

        // 复杂正则不应该导致性能问题
        $complexPattern = '^/api/(v[1-9][0-9]*/)*(users|posts|comments)/[0-9]+(/[a-z]+)*$';
        $this->assertTrue($this->matcher->matches('/api/v1/users/123', $complexPattern));
        $this->assertTrue($this->matcher->matches('/api/v2/posts/456/comments', $complexPattern));
    }

    /**
     * 测试案例敏感性
     */
    public function testMatchesCaseSensitivity(): void
    {
        $this->assertFalse($this->matcher->matches('/Billing/Invoice', '/billing/invoice'));
        $this->assertFalse($this->matcher->matches('/ADMIN/users', '^/admin/.*'));
        $this->assertTrue($this->matcher->matches('/admin/Users', '^/admin/.*'));
    }

    /**
     * 测试可以从容器中正确获取服务
     */
    public function testCanBeInstantiatedFromContainer(): void
    {
        $this->assertInstanceOf(RoutePatternMatcher::class, $this->matcher);
    }

    /**
     * 测试服务在集成环境中的完整功能
     */
    public function testIntegrationFunctionality(): void
    {
        // 测试服务的核心功能是否正常工作
        $this->assertTrue($this->matcher->matches('/test', '/test'));
        $this->assertTrue($this->matcher->matches('/test/path', '/test/*'));
        $this->assertTrue($this->matcher->matches('/test/deep/path', '/test/**'));

        // 测试 matchesAny 方法
        $patterns = ['/admin/*', '/billing/*'];
        $this->assertTrue($this->matcher->matchesAny('/admin/users', $patterns));
        $this->assertFalse($this->matcher->matchesAny('/public/home', $patterns));

        // 测试模式验证功能
        $this->assertTrue($this->matcher->isValidPattern('/admin/*'));
        $this->assertFalse($this->matcher->isValidPattern(''));

        // 测试模式类型识别
        $this->assertEquals('exact', $this->matcher->getPatternType('/admin'));
        $this->assertEquals('wildcard', $this->matcher->getPatternType('/admin/*'));
        $this->assertEquals('regex', $this->matcher->getPatternType('^/admin/.*'));
    }
}
