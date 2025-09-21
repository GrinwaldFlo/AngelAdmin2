# Contributing to AngelAdmin2

Thank you for considering contributing to AngelAdmin2! We welcome contributions from everyone.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check [existing issues](https://github.com/GrinwaldFlo/AngelAdmin2/issues) to avoid duplicates. When you create a bug report, please include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** to demonstrate the steps
- **Describe the behavior you observed** and what behavior you expected
- **Include screenshots or animated GIFs** if they help explain the problem
- **Specify the PHP version, CakePHP version, and operating system**

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **A clear and descriptive title**
- **A detailed description of the suggested enhancement**
- **Explain why this enhancement would be useful** to AngelAdmin2 users
- **List some other applications where this enhancement exists** if applicable

### Pull Requests

1. **Fork the repository** and create your branch from `master`
2. **Follow the coding standards** described below
3. **Add tests** for any new functionality
4. **Ensure all tests pass**
5. **Update documentation** as needed
6. **Create a pull request** with a clear title and description

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL/MariaDB
- Git

### Setup Steps

1. Fork and clone the repository
   ```bash
   git clone https://github.com/your-username/AngelAdmin2.git
   cd AngelAdmin2
   ```

2. Install dependencies
   ```bash
   composer install
   ```

3. Copy and configure the environment
   ```bash
   cp config/app_local.example.php config/app_local.php
   # Edit config/app_local.php with your database settings
   ```

4. Run migrations
   ```bash
   bin/cake migrations migrate
   ```

5. Start the development server
   ```bash
   bin/cake server -p 8765
   ```

## Coding Standards

### PHP Standards

We follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards and CakePHP conventions:

- Use 4 spaces for indentation (no tabs)
- Keep lines under 120 characters when possible
- Use meaningful variable and method names
- Add PHPDoc comments for classes and methods
- Follow CakePHP naming conventions

### Code Style Checking

Run code style checks before submitting:

```bash
# Check code style
composer cs-check

# Automatically fix code style issues
composer cs-fix
```

### Database Conventions

- Table names should be plural and snake_case (e.g., `team_members`)
- Column names should be snake_case
- Foreign keys should follow the pattern `table_id` (e.g., `member_id`)
- Use meaningful constraint names

### Testing

- Write unit tests for new models and components
- Write integration tests for controllers
- Ensure all tests pass before submitting

```bash
# Run all tests
composer test

# Run specific test files
./vendor/bin/phpunit tests/TestCase/Model/Table/MembersTableTest.php
```

## Commit Messages

Use clear and meaningful commit messages:

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests liberally after the first line

Example:
```
Add member photo upload functionality

- Implement image upload and validation
- Add image resizing and thumbnail generation
- Update member profile templates
- Add tests for photo upload component

Fixes #123
```

## Branch Naming

Use descriptive branch names:

- `feature/member-photo-upload`
- `fix/billing-calculation-error`
- `improvement/performance-optimization`
- `docs/api-documentation`

## Documentation

### Code Documentation

- Add PHPDoc comments for all classes, methods, and properties
- Include parameter types and return types
- Document complex business logic with inline comments
- Keep comments up to date with code changes

### User Documentation

- Update README.md if you add new features
- Add or update installation/configuration instructions
- Document new API endpoints or significant changes

## Security

### Reporting Security Vulnerabilities

**Do not create public GitHub issues for security vulnerabilities.**

Instead, please email security concerns to: [security@example.com](mailto:security@example.com)

### Security Guidelines

- Never commit sensitive information (passwords, API keys, etc.)
- Use parameterized queries to prevent SQL injection
- Validate and sanitize all user input
- Follow CakePHP security best practices
- Use HTTPS in production environments

## Review Process

1. **Automated checks** must pass (tests, code style, etc.)
2. **Code review** by at least one maintainer
3. **Manual testing** for UI changes or complex features
4. **Documentation review** for user-facing changes

## Getting Help

- **Documentation**: Check the project README and inline documentation
- **Community**: Create a GitHub Discussion for questions
- **Issues**: Search existing issues before creating new ones

## Recognition

Contributors will be recognized in:
- The project README
- Release notes for significant contributions
- Special mentions in the community

Thank you for contributing to AngelAdmin2! ??
