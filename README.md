# Idle Lock Screen Bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo)](https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

A Symfony bundle that provides automatic idle lock screen functionality. When users are inactive on sensitive pages for a configured period, the application automatically redirects them to a password verification screen.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
  - [1. Install via Composer](#1-install-via-composer)
  - [2. Register the Bundle](#2-register-the-bundle)
  - [3. Create Database Tables](#3-create-database-tables)
- [Quick Start](#quick-start)
  - [1. Template Integration](#1-template-integration)
  - [2. Configure Lock Rules](#2-configure-lock-rules)
  - [3. Route Pattern Syntax](#3-route-pattern-syntax)
- [Configuration](#configuration)
  - [Programmatic Configuration](#programmatic-configuration)
  - [Database Configuration](#database-configuration)
- [API Reference](#api-reference)
  - [Twig Functions](#twig-functions)
  - [Services](#services)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Contributing](#contributing)

## Features

- ✅ **Route-based Configuration**: Configure lock rules based on route patterns with wildcard and regex support
- ✅ **Flexible Timeout Settings**: Configurable idle timeout periods (1-86400 seconds)
- ✅ **Smart Activity Detection**: Automatically detects user activity (mouse, keyboard, touch events)
- ✅ **Server-side Session Management**: Lock state persisted server-side to prevent client-side bypassing
- ✅ **Complete Audit Logging**: Full logging of all lock/unlock operations for security auditing
- ✅ **Non-intrusive Integration**: Simple one-line template integration
- ✅ **Modern UI**: Clean, responsive lock screen interface
- ✅ **Security Features**: Protection against open redirect attacks and bypass attempts
- ✅ **Mobile Friendly**: Responsive design for mobile devices

## Installation

### 1. Install via Composer

```bash
composer require tourze/idle-lock-screen-bundle
```

### 2. Register the Bundle

Add to `config/bundles.php`:

```php
return [
    // ...
    Tourze\IdleLockScreenBundle\IdleLockScreenBundle::class => ['all' => true],
];
```

### 3. Create Database Tables

Run database migrations:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Or create the table structure manually:

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

## Quick Start

### 1. Template Integration

Add the idle lock script to your base template (e.g., `templates/base.html.twig`) before the closing `</body>` tag:

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{% block title %}Welcome!{% endblock %}</title>
</head>
<body>
    {% block body %}{% endblock %}
    
    <!-- Add idle lock script at the bottom of the page -->
    {{ idle_lock_script() }}
</body>
</html>
```

### 2. Configure Lock Rules

Create lock configurations via code:

```php
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;

public function configureIdleLock(IdleLockDetector $detector): void
{
    // Configure billing pages with 60-second timeout
    $detector->createConfiguration(
        routePattern: '/billing/*',
        timeoutSeconds: 60,
        isEnabled: true,
        description: 'Billing related pages'
    );
    
    // Configure admin panel with 30-second timeout
    $detector->createConfiguration(
        routePattern: '^/admin/.*',
        timeoutSeconds: 30,
        isEnabled: true,
        description: 'Admin panel pages'
    );
}
```

### 3. Route Pattern Syntax

The bundle supports three pattern matching modes:

#### Exact Match
```text
/billing/invoice
```

#### Wildcard Match
```text
/billing/*          # Matches all single-level paths under /billing/
/billing/**         # Matches all multi-level paths under /billing/
```

#### Regular Expression Match
```text
^/admin/.*          # Matches all paths starting with /admin/
/billing/(invoice|payment)  # Matches /billing/invoice or /billing/payment
```

## Configuration

### Programmatic Configuration

```php
use Tourze\IdleLockScreenBundle\Service\IdleLockDetector;

// In a controller or service
public function setupLockRules(IdleLockDetector $detector): void
{
    // Create a new configuration
    $config = $detector->createConfiguration(
        routePattern: '/sensitive-area/*',
        timeoutSeconds: 120,
        isEnabled: true,
        description: 'Sensitive area requiring frequent verification'
    );
    
    // Check if a route should be locked
    $shouldLock = $detector->shouldLockRoute('/billing/invoice');
    
    // Get configuration for a specific route
    $config = $detector->getRouteConfiguration('/billing/invoice');
}
```

### Database Configuration

Insert configurations directly into the database:

```sql
INSERT INTO idle_lock_configuration (route_pattern, timeout_seconds, is_enabled, description, created_at, updated_at) VALUES
('/billing/*', 60, 1, 'Billing related pages', NOW(), NOW()),
('/account/sensitive', 30, 1, 'Sensitive account pages', NOW(), NOW()),
('^/admin/.*', 30, 1, 'Admin panel pages', NOW(), NOW());
```

## API Reference

### Twig Functions

#### `idle_lock_script()`
Renders the idle lock JavaScript code. Only outputs the script when the current route has lock enabled and the session is not locked.

#### `is_idle_lock_enabled()`
Checks if idle lock is enabled for the current route.

#### `idle_lock_timeout()`
Gets the timeout duration in seconds for the current route.

Example usage in templates:

```html
{% if is_idle_lock_enabled() %}
    <div class="alert alert-info">
        This page has idle lock enabled. Timeout: {{ idle_lock_timeout() }} seconds
    </div>
{% endif %}
```

### Services

#### `IdleLockDetector`
Service for managing lock configurations.

```php
// Check if a route should be locked
$shouldLock = $detector->shouldLockRoute('/billing/invoice');

// Get route configuration
$config = $detector->getRouteConfiguration('/billing/invoice');

// Create new configuration
$config = $detector->createConfiguration('/new-route/*', 60);
```

#### `LockManager`
Service for managing lock state.

```php
// Lock the session
$lockManager->lockSession('/billing/invoice', 'idle_timeout');

// Unlock the session
$lockManager->unlockSession();

// Check lock status
$isLocked = $lockManager->isSessionLocked();

// Get user lock history
$history = $lockManager->getUserLockHistory();
```

#### `RoutePatternMatcher`
Service for pattern matching.

```php
// Check if route matches pattern
$matches = $matcher->matches('/billing/invoice', '/billing/*');

// Validate pattern syntax
$isValid = $matcher->isValidPattern('^/admin/.*');
```

## Security

1. **Password Verification**: Implement secure password verification logic
2. **Session Security**: Lock state is stored server-side and cannot be tampered with by clients
3. **Redirect Security**: Built-in protection against open redirect attacks
4. **Audit Logging**: Complete logging of all lock-related operations for security auditing

### Custom Password Verification

You can customize the password verification logic by extending the unlock controller:

```php
use Tourze\IdleLockScreenBundle\Controller\IdleLockScreenUnlockController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomUnlockController extends IdleLockScreenUnlockController
{
    public function __construct(
        // ... parent constructor parameters
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct(/* ... */);
    }

    // Override password verification logic
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

## Troubleshooting

### Script Not Injected
Ensure you've called `{{ idle_lock_script() }}` in your template and the current route has lock configuration enabled.

### Lock Not Working
1. Check if the route pattern correctly matches the current route
2. Verify the configuration is enabled (`is_enabled = true`)
3. Check browser console for JavaScript errors

### Cannot Unlock
1. Verify password verification logic is correctly implemented
2. Check if user session is valid

## License

MIT License

## Contributing

Issues and Pull Requests are welcome!