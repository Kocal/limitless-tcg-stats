# AGENTS.md - AI Coding Agent Instructions

This document provides essential information for AI coding agents operating in this repository.

## Project Overview

A Symfony 8.0 application running on PHP 8.5+ with FrankenPHP (Caddy-based PHP application server).
Generated using [Symfony Docker](https://github.com/dunglas/symfony-docker).

**Stack:**
- **Framework:** Symfony 8.0
- **PHP Version:** 8.5+
- **Web Server:** FrankenPHP (Caddy)
- **API Preloading:** Vulcain
- **Container:** Docker with multi-stage builds

## Build, Lint, and Test Commands

All commands run via Docker Compose through the Makefile.

### Docker Operations

```bash
make build       # Build Docker images (with --pull --no-cache)
make up          # Start containers in detached mode
make start       # Build and start containers
make down        # Stop and remove containers
make logs        # Show live container logs
make sh          # Open shell in PHP container
make bash        # Open bash in PHP container (with history)
```

### Testing

```bash
# Run all tests
make test

# Run specific test file
make test c="tests/Unit/MyTest.php"

# Run specific test method
make test c="--filter testMethodName"

# Run tests in a specific group
make test c="--group e2e"

# Stop on first failure
make test c="--stop-on-failure"

# Combine options
make test c="--filter testMethodName --stop-on-failure"
```

Tests run with `APP_ENV=test` automatically.

### Linting and Code Style

```bash
# Fix all code style issues
make php-cs-fixer c='fix'

# Check without fixing (CI mode)
make php-cs-fixer c='fix --dry-run --diff'

# Fix specific directory
make php-cs-fixer c='fix src/Controller'
```

**CI runs:** `make php-cs-fixer c='fix --dry-run --diff'` - ensure this passes before committing.

### Composer

```bash
make composer c='require package/name'   # Add dependency
make composer c='require --dev package'  # Add dev dependency
make composer c='update'                 # Update dependencies
make vendor                              # Install production deps only
```

### Symfony Console

```bash
make sf                    # List all Symfony commands
make sf c='about'          # Show Symfony info
make sf c='debug:router'   # List routes
make cc                    # Clear cache
```

## Code Style Guidelines

### PHP-CS-Fixer Configuration

Uses `@Symfony` ruleset (see `.php-cs-fixer.dist.php`). Key rules:
- PSR-12 base with Symfony additions
- Short array syntax `[]`
- Single blank line before namespace
- Ordered imports (alphabetically)
- No unused imports
- Single quotes for simple strings
- Trailing commas in multiline arrays

### Import Organization

Imports are organized alphabetically. Example:

```php
<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
```

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `UserController`, `OrderService` |
| Methods | camelCase | `findByEmail()`, `createOrder()` |
| Variables | camelCase | `$userName`, `$orderTotal` |
| Constants | UPPER_SNAKE | `MAX_RETRIES`, `DEFAULT_LIMIT` |
| Services | camelCase | `userService`, `orderRepository` |
| Config files | kebab-case | `doctrine.yaml`, `security.yaml` |
| Routes | snake_case | `user_profile`, `order_create` |

### File Naming

- PHP classes: Match class name exactly (`UserController.php`)
- Config: lowercase with `.yaml` extension
- Tests: `*Test.php` suffix in `tests/` directory

### Indentation

From `.editorconfig`:
- **PHP files:** 4 spaces
- **YAML files:** 2 spaces (for compose/GitHub workflows), 4 spaces elsewhere
- **Dockerfile, Caddyfile, shell scripts:** tabs
- **Makefile:** tabs

## Directory Structure

```
src/
├── Controller/     # HTTP controllers
├── Entity/         # Doctrine entities
├── Repository/     # Doctrine repositories
├── Service/        # Business logic services
├── Command/        # Console commands
├── EventListener/  # Event listeners
├── Form/           # Form types
└── Kernel.php      # Application kernel

tests/
├── Unit/           # Unit tests
├── Integration/    # Integration tests
└── Functional/     # Functional/E2E tests

config/
├── packages/       # Bundle configurations
├── routes/         # Route configurations
├── bundles.php     # Registered bundles
├── routes.yaml     # Main routing
└── services.yaml   # DI container config
```

## Namespacing

PSR-4 autoloading:
- `App\` maps to `src/`
- `App\Tests\` maps to `tests/`

## Error Handling

- Use Symfony's exception system
- Throw specific exceptions (`NotFoundHttpException`, `AccessDeniedHttpException`)
- Log errors using PSR-3 logger (`LoggerInterface`)
- Environment-aware error display (`APP_ENV=dev` shows details, `prod` shows generic)

## Environment Configuration

- `.env` - Default values (committed)
- `.env.local` - Local overrides (gitignored)
- `.env.test` - Test environment (committed)
- `.env.test.local` - Local test overrides (gitignored)

Key variables:
- `APP_ENV` - Environment: `dev`, `test`, `prod`
- `APP_SECRET` - Application secret key
- `DATABASE_URL` - Database connection string

## Dev Container Network Sandboxing

This project runs in a Dev Container with an outbound firewall. If network requests fail, add domains to `.devcontainer/init-firewall.sh`:

```bash
ipset=/github.com/anthropic.com/NEW_DOMAIN.com/allowed-domains
```

Then rebuild the Dev Container.

## CI Pipeline

On push to `main` or pull requests:
1. Build Docker images (with caching)
2. Start services
3. Check HTTP/HTTPS reachability
4. Run PHP-CS-Fixer (lint check)

PHPUnit runs when configured (currently commented in CI).

## Common Patterns

### Controller

```php
#[Route('/api/users', name: 'api_users_')]
final class UserController extends AbstractController
{
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->json($user);
    }
}
```

### Service with Dependency Injection

```php
final readonly class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private LoggerInterface $logger,
    ) {}
}
```

### Before Committing

1. Run `make php-cs-fixer c='fix'` to fix code style
2. Run `make test` to ensure tests pass
3. Verify `make php-cs-fixer c='fix --dry-run --diff'` shows no changes
