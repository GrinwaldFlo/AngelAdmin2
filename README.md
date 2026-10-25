# AngelAdmin2

[![CakePHP](https://img.shields.io/badge/CakePHP-5.2-red.svg?style=flat-square)](https://cakephp.org)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

A comprehensive membership and team management system built with CakePHP 5.2, designed for sports clubs and organizations. AngelAdmin2 provides a complete solution for managing members, teams, meetings, billing, and administrative tasks.

## ✨ Features

### 👥 Member Management
- **Complete member profiles** with personal information, contact details, and photos
- **Team assignments** and multi-team support
- **Registration workflow** with digital signatures
- **Age-based categorization** (adults/children)
- **Member status tracking** (active, inactive, registered)
- **Hash-based authentication** for secure member access without passwords

### ⚽ Team & Site Management
- **Multi-site support** for organizations with multiple locations
- **Team organization** with coaches and member assignments
- **Hierarchical structure** with sites containing multiple teams

### 💰 Billing & Finance
- **Automated billing system** with customizable templates
- **Swiss QR-bill support** for seamless payments
- **Multi-payment options** and installment plans
- **Late payment tracking** and reminder system
- **Family discounts** and special pricing
- **Payment status monitoring**

### 📅 Meeting & Event Management
- **Meeting scheduling** with different types (small, big, doodle)
- **Attendance tracking** and presence management
- **Event notifications** and reminders

### 🔒 Security & Authentication
- **Role-based access control** with granular permissions
- **Dual authentication system**: traditional login and hash-based access
- **Secure hash cookies** for member self-service
- **Session management** with configurable timeouts

### 🛠️ Administrative Tools
- **Comprehensive dashboards** with statistics
- **Member data export** and reporting
- **Configuration management** for system settings
- **Content management** for announcements and information
- **Email integration** for communications

### 🌍 Internationalization
- **Multi-language support** (English/French)
- **Localized date and currency formatting**
- **Configurable default locale**

## 🚀 Getting Started

### Prerequisites

- **PHP 8.1+** with required extensions
- **MySQL/MariaDB** database
- **Composer** for dependency management
- **Web server** (Apache/Nginx) with URL rewriting support

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/GrinwaldFlo/AngelAdmin2.git
   cd AngelAdmin2
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure the environment and database**
   - Copy `.env.example` to `.env` and adjust the values (see [Environment Variables](#environment-variables))
   - Set the database connection with `DATABASE_HOST`, `DATABASE_NAME`, `DATABASE_USER` and `DATABASE_PASSWORD` (or a single `DATABASE_URL`)
   - `config/app.php` reads every environment specific value from these variables. An optional, git-ignored `config/app_local.php` can still override any key on one machine.

4. **Set up the database**
   ```bash
   # Run migrations to create database tables
   bin/cake migrations migrate
   ```

5. **Configure security**
   - Generate a security salt and set `SECURITY_SALT` in `.env` or in the environment. The application does not start without it.
   ```bash
   # Generate a random salt
   openssl rand -hex 32
   ```

6. **Set file permissions**
   ```bash
   # Make writable directories
   chmod -R 755 tmp/
   chmod -R 755 logs/
   chmod -R 755 webroot/
   ```

7. **Start the development server**
   ```bash
   bin/cake server -p 8765
   ```

   Visit `http://localhost:8765` to access the application.

## ⚙️ Configuration

### Environment Variables

Copy `.env.example` to `.env` in the root directory (it lists every supported variable), or set these environment variables:

```bash
# Security
SECURITY_SALT=your_32_character_random_string

# Database
DATABASE_URL=mysql://username:password@localhost/angeladmin2

# Email
EMAIL_TRANSPORT_DEFAULT_URL=smtp://username:password@mail.example.com:587

# Debug (disable in production)
DEBUG=false
```

### Email Configuration

Configure the SMTP server in `.env`:

```bash
EMAIL_HOST=your-smtp-server.com
EMAIL_PORT=587
EMAIL_USER=your-email@example.com
EMAIL_PASSWORD=your-password
EMAIL_TLS=true
```

### Application Settings

The application uses a configuration system accessible through the admin panel:
- Site settings and contact information
- Season dates and membership rules
- Language preferences
- Logo and branding customization

## 📁 Project Structure

```
AngelAdmin2/
├── config/              # Configuration files
│   └── Migrations/      # Database migrations
├── docs/                # Additional documentation
├── plugins/             # Local CakePHP plugins
├── resources/           # Translations and other resources
├── src/
│   ├── Command/         # CLI commands (bin/cake ...)
│   ├── Console/         # Composer installer hooks
│   ├── Controller/      # Application controllers and components
│   ├── Error/           # Custom error logging
│   ├── Middleware/      # Security and maintenance middleware
│   ├── Model/           # Tables, entities and behaviors
│   ├── Policy/          # Authorization policies
│   ├── Utility/         # Shared helper classes
│   └── View/            # View helpers and custom view classes
├── templates/           # View templates
├── webroot/             # Public web files (CSS, JS, images)
├── tests/
│   ├── Fixture/         # Test fixtures (table schemas and sample rows)
│   └── TestCase/        # Unit and integration tests
├── logs/                # Application logs
├── tmp/                 # Temporary files, cache and test database
└── vendor/              # Composer dependencies
```

## 🔧 Key Components

### Authentication Methods

1. **Traditional Login**: Username/password authentication for administrators and coaches
2. **Hash Authentication**: Secure hash-based access for members to view their own data

### Database Schema

Main entities:
- **Members**: Core member information and relationships
- **Teams**: Team organization and site assignment
- **Sites**: Multi-location support
- **Bills**: Billing and payment tracking
- **Meetings**: Event and meeting management
- **Users**: System user accounts with role-based permissions

### API Endpoints

The system provides various endpoints for:
- Member data retrieval
- Bill generation and management
- Meeting scheduling
- Administrative functions

## 🧪 Testing

The test suite uses [PHPUnit](https://phpunit.de/) with CakePHP's test framework. Tests live in `tests/TestCase/` and their sample data in `tests/Fixture/`.

### Requirements

- Development dependencies installed (`composer install`, without `--no-dev`)
- PHP extensions `pdo_sqlite`, `mbstring`, `intl` and `zip`
- No `.env` is needed: `phpunit.xml.dist` provides a test `SECURITY_SALT`

No MySQL server is needed: by default the `test` datasource uses a SQLite file at `tmp/tests.sqlite`. `tests/bootstrap.php` builds its tables by running the migrations in `config/Migrations`, then each test fills them with rows from its fixtures. If you add a table, add a migration for it, or the tests will not see it. To use another database, set `DATABASE_TEST_URL`:

```bash
DATABASE_TEST_URL="mysql://user:password@localhost/angeladmin2_test" composer test
```

The tests never touch the `default` database: `tests/bootstrap.php` aliases it to the `test` connection.

### Running tests

```bash
# Run all tests
composer test

# Run one directory
./vendor/bin/phpunit tests/TestCase/Controller/
./vendor/bin/phpunit tests/TestCase/Model/

# Run one test file
./vendor/bin/phpunit tests/TestCase/Controller/MembersControllerTest.php

# Run tests whose name matches a pattern
./vendor/bin/phpunit --filter testIndex

# Run the tests and the code style check
composer check
```

Some generated tests are still marked as incomplete or skipped; they show up as `I` and `S` in the output and do not fail the run.

### Continuous integration

GitHub Actions (`.github/workflows/phpunit.yml`) runs `composer test` on PHP 8.3 for every pull request and every push to `master`.

### Code style

```bash
# Check code style
composer cs-check

# Fix code style issues
composer cs-fix
```

## 📦 Dependencies

### Core Dependencies
- **CakePHP 5.2**: Web framework
- **Authentication & Authorization**: User management
- **Migrations**: Database schema management
- **Bootstrap UI**: Frontend framework integration
- **Intervention Image**: Image processing
- **Mobile Detect**: Device detection
- **Swiss QR-Bill**: Payment slip generation
- **Symfony String & Emoji**: String handling
- **TinyMCE**: Rich text editor
- **TCPDF**: PDF generation

### Development Dependencies
- **PHPUnit**: Testing framework
- **CakePHP Bake**: Code generation
- **Debug Kit**: Development debugging
- **Code Sniffer**: Code quality
- **PHP Dotenv**: Loads the `.env` file

## 🚢 Deployment

### Production Deployment

1. **Server Requirements**
   - PHP 8.1+ with extensions: mbstring, intl, simplexml, PDO
   - MySQL/MariaDB 5.7+
   - Web server with URL rewriting

2. **Environment Setup**
   ```bash
   # Install dependencies (production only)
   composer install --no-dev --optimize-autoloader
   
   # Set production environment
   export CAKE_ENV=production
   
   # Clear and warm up cache
   bin/cake cache clear_all
   ```

3. **Security Checklist**
   - Set `DEBUG=false` in production
   - Configure proper file permissions
   - Use HTTPS for secure communication
   - Implement proper backup procedures
   - Monitor logs for security issues

### Docker Support

A Docker configuration can be added for containerized deployment:

```dockerfile
# Example Dockerfile structure
FROM php:8.1-apache
# Install PHP extensions and configure Apache
# Copy application files
# Set proper permissions
```

## 🤝 Contributing

We welcome contributions! See [CONTRIBUTING.md](CONTRIBUTING.md) for details, and please follow these guidelines:

1. **Fork the repository** and create your feature branch
2. **Follow coding standards**: Use CakePHP conventions and PSR-12
3. **Write tests** for new functionality
4. **Update documentation** as needed
5. **Submit a pull request** with a clear description

### Development Setup

```bash
# Clone your fork
git clone https://github.com/your-username/AngelAdmin2.git

# Install development dependencies
composer install

# Set up pre-commit hooks (optional)
# This ensures code quality before commits
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 💬 Support

- **Documentation**: Check the [docs](docs/) directory for detailed guides (installation, security, HEIC support, favicons)
- **Issues**: Report bugs via [GitHub Issues](https://github.com/GrinwaldFlo/AngelAdmin2/issues)
- **Community**: Join our discussions for questions and support

## 🗺️ Roadmap

Future enhancements planned:
- [ ] Mobile application companion
- [ ] Advanced reporting and analytics
- [ ] Integration with external payment systems
- [ ] Enhanced notification system
- [ ] API documentation and external integrations

## 👨‍💻 Authors

- **Florian Grinwald** - *Initial work* - [@GrinwaldFlo](https://github.com/GrinwaldFlo)

## 🙏 Acknowledgments

- Built with [CakePHP](https://cakephp.org/) framework
- UI components from [Bootstrap](https://getbootstrap.com/)
- Icons and fonts from various open-source projects
- Community contributors and testers

---

**AngelAdmin2** - Streamlining sports club management with modern web technology.


