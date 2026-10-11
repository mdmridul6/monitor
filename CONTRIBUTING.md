# Contributing to Laravel Monitor

Thank you for considering contributing to Laravel Monitor! This document outlines the process for contributing to this project.

## Code of Conduct

Please note that this project is released with a Contributor Code of Conduct. By participating in this project you agree to abide by its terms.

## How to Contribute

### Reporting Issues

Before submitting an issue, please check if it has already been reported. When submitting an issue, please include:

- Laravel version
- PHP version
- Package version
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- Any relevant stack traces or error messages

### Pull Requests

1. Fork the repository
2. Create a new branch for your feature or fix (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Ensure your code follows the existing style
5. Add or update tests as needed
6. Run the test suite to ensure everything passes
7. Commit your changes (`git commit -m 'Add amazing feature'`)
8. Push to the branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy the example environment file: `cp .env.example .env`
4. Generate an application key: `php artisan key:generate`
5. Run the test suite: `vendor/bin/phpunit`

### Coding Standards

This project follows PSR-12 and Laravel coding standards. Please ensure your code:

- Follows PSR-12
- Uses type hints where possible
- Includes proper documentation
- Follows Laravel's naming conventions
- Includes tests for new features or bug fixes

### Security

If you discover a security vulnerability, please follow the security policy outlined in SECURITY.md instead of opening a public issue.

## Running Tests

To run the test suite:

```bash
composer test
```

Or with PHPUnit directly:

```bash
vendor/bin/phpunit
```

To run tests with coverage:

```bash
vendor/bin/phpunit --coverage-html coverage
```

## License

By contributing to Laravel Monitor, you agree that your contributions will be licensed under the MIT License.
