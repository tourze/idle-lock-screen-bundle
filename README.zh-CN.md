# Idle Lock Screen Bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo)](https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

无操作锁屏 Bundle 为 Symfony 应用提供自动锁定功能，当用户在敏感页面长时间无操作时，自动跳转到密码验证页面。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
  - [1. 通过 Composer 安装](#1-通过-composer-安装)
  - [2. 注册 Bundle](#2-注册-bundle)
  - [3. 创建数据库表](#3-创建数据库表)
- [使用方法](#使用方法)
  - [1. 在模板中集成](#1-在模板中集成)
  - [2. 配置锁定规则](#2-配置锁定规则)
    - [通过代码配置](#通过代码配置)
    - [通过数据库直接配置](#通过数据库直接配置)
  - [3. 路由模式说明](#3-路由模式说明)
    - [精确匹配](#精确匹配)
    - [通配符匹配](#通配符匹配)
    - [正则表达式匹配](#正则表达式匹配)
  - [4. 检查锁定状态](#4-检查锁定状态)
  - [5. 自定义密码验证](#5-自定义密码验证)
- [API 参考](#api-参考)
  - [Twig 函数](#twig-函数)
    - [`idle_lock_script()`](#idle_lock_script)
    - [`is_idle_lock_enabled()`](#is_idle_lock_enabled)
    - [`idle_lock_timeout()`](#idle_lock_timeout)
  - [服务](#服务)
    - [`IdleLockDetector`](#idlelockdetector)
    - [`LockManager`](#lockmanager)
    - [`RoutePatternMatcher`](#routepatternmatcher)
- [路由](#路由)
- [安全考虑](#安全考虑)
- [故障排除](#故障排除)
  - [脚本未注入](#脚本未注入)
  - [锁定不生效](#锁定不生效)
  - [无法解锁](#无法解锁)
- [许可证](#许可证)
- [贡献](#贡献)

## 功能特性

- ✅ 支持基于路由规则的页面锁定配置（支持通配符和正则表达式）
- ✅ 可配置的无操作超时时间
- ✅ 自动检测用户活动状态（鼠标、键盘、触摸等）
- ✅ 锁定状态持久化存储，防止用户绕过验证
- ✅ 完整的锁定/解锁记录日志
- ✅ 无侵入式 Twig 模板集成
- ✅ 现代化的锁定页面 UI
- ✅ 防止开放重定向攻击
- ✅ 支持移动端

## 安装

### 1. 通过 Composer 安装

```bash
composer require tourze/idle-lock-screen-bundle
```

### 2. 注册 Bundle

在 `config/bundles.php` 中添加：

```php
return [
    // ...
    Tourze\IdleLockScreenBundle\IdleLockScreenBundle::class => ['all' => true],
];
```

### 3. 创建数据库表

运行数据库迁移：

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

或者直接创建表结构：

```sql
CREATE TABLE idle_lock_configuration (
    id INT AUTO_INCREMENT NOT NULL,
    route_pattern VARCHAR(255) NOT NULL,
    timeout_seconds INT NOT NULL,
    is_enabled TINYINT(1) NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id),
    INDEX idx_route_pattern (route_pattern),
    INDEX idx_is_enabled (is_enabled)
);

CREATE TABLE idle_lock_record (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(128) NOT NULL,
    action_type VARCHAR(20) NOT NULL,
    route VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent LONGTEXT DEFAULT NULL,
    context JSON DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_action_type (action_type),
    INDEX idx_user_session (user_id, session_id)
);
```

## 使用方法

### 1. 在模板中集成

在您的基础模板（如 `templates/base.html.twig`）的 `</body>` 标签前添加：

```html
{{ idle_lock_script() }}
```

完整示例：

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{% block title %}Welcome!{% endblock %}</title>
    <!-- 其他 head 内容 -->
</head>
<body>
    {% block body %}{% endblock %}
    
    <!-- 在页面底部添加无操作锁定脚本 -->
    {{ idle_lock_script() }}
</body>
</html>
```

### 2. 配置锁定规则

#### 通过代码配置

```php
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;

// 在控制器或服务中
public function configureIdleLock(IdleLockDetector $detector): void
{
    // 配置账单页面，60秒超时
    $detector->createConfiguration(
        routePattern: '/billing/*',
        timeoutSeconds: 60,
        isEnabled: true,
        description: '账单相关页面'
    );
    
    // 配置管理后台，30秒超时
    $detector->createConfiguration(
        routePattern: '^/admin/.*',
        timeoutSeconds: 30,
        isEnabled: true,
        description: '管理后台页面'
    );
}
```

#### 通过数据库直接配置

```sql
INSERT INTO idle_lock_configuration (route_pattern, timeout_seconds, is_enabled, description, created_at, updated_at) VALUES
('/billing/*', 60, 1, '账单相关页面', NOW(), NOW()),
('/account/sensitive', 30, 1, '敏感账户页面', NOW(), NOW()),
('^/admin/.*', 30, 1, '管理后台页面', NOW(), NOW());
```

### 3. 路由模式说明

支持三种匹配模式：

#### 精确匹配
```text
/billing/invoice
```

#### 通配符匹配
```text
/billing/*          # 匹配 /billing/ 下的所有单级路径
/billing/**         # 匹配 /billing/ 下的所有多级路径
```

#### 正则表达式匹配
```text
^/admin/.*          # 匹配所有以 /admin/ 开头的路径
/billing/(invoice|payment)  # 匹配 /billing/invoice 或 /billing/payment
```

### 4. 检查锁定状态

在模板中检查当前页面是否启用了锁定：

```html
{% if is_idle_lock_enabled() %}
    <div class="alert alert-info">
        此页面启用了无操作锁定，超时时间：{{ idle_lock_timeout() }} 秒
    </div>
{% endif %}
```

### 5. 自定义密码验证

默认情况下，Bundle 使用简单的密码验证。您可以通过继承控制器来自定义验证逻辑：

```php
use Tourze\IdleLockScreenBundle\Controller\IdleLockController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomIdleLockController extends IdleLockController
{
    public function __construct(
        // ... 父类构造函数参数
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct(/* ... */);
    }

    protected function verifyPassword(string $password): bool
    {
        $user = $this->security->getUser();
        if (!$user) {
            return false;
        }

        return $this->passwordHasher->isPasswordValid($user, $password);
    }
}
```

## API 参考

### Twig 函数

#### `idle_lock_script()`
渲染无操作锁定的 JavaScript 代码。只有在当前路由启用了锁定且会话未被锁定时才会输出脚本。

#### `is_idle_lock_enabled()`
检查当前路由是否启用了无操作锁定。

#### `idle_lock_timeout()`
获取当前路由的超时时间（秒）。

### 服务

#### `IdleLockDetector`
锁定检测服务，用于管理锁定配置。

```php
// 检查路由是否需要锁定
$shouldLock = $detector->shouldLockRoute('/billing/invoice');

// 获取路由配置
$config = $detector->getRouteConfiguration('/billing/invoice');

// 创建新配置
$config = $detector->createConfiguration('/new-route/*', 60);
```

#### `LockManager`
锁定管理服务，用于管理锁定状态。

```php
// 锁定会话
$lockManager->lockSession('/billing/invoice', 'idle_timeout');

// 解锁会话
$lockManager->unlockSession();

// 检查锁定状态
$isLocked = $lockManager->isSessionLocked();

// 获取锁定历史
$history = $lockManager->getUserLockHistory();
```

#### `RoutePatternMatcher`
路由模式匹配器。

```php
// 检查路由是否匹配模式
$matches = $matcher->matches('/billing/invoice', '/billing/*');

// 验证模式是否有效
$isValid = $matcher->isValidPattern('^/admin/.*');
```

## 路由

Bundle 提供以下路由：

- `GET|POST /idle-lock/timeout` - 锁定页面和处理超时请求
- `POST /idle-lock/unlock` - 处理解锁验证
- `GET /idle-lock/status` - 获取锁定状态（AJAX）

## 安全考虑

1. **密码验证**：确保实现安全的密码验证逻辑
2. **会话安全**：锁定状态存储在服务器端，无法被客户端篡改
3. **重定向安全**：内置防开放重定向攻击保护
4. **日志记录**：完整记录所有锁定相关操作，便于审计

## 故障排除

### 脚本未注入
确保在模板中调用了 `{{ idle_lock_script() }}` 且当前路由已配置锁定规则。

### 锁定不生效
1. 检查路由模式是否正确匹配
2. 确认配置已启用（`is_enabled = true`）
3. 检查浏览器控制台是否有 JavaScript 错误

### 无法解锁
1. 确认密码验证逻辑正确实现
2. 检查用户会话是否有效

## 许可证

MIT License

## 贡献

欢迎提交 Issue 和 Pull Request！