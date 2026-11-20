# Installation Guide

How to install, configure, update and test AngelAdmin2, on a development machine or a production server. For a presentation of the product, see the [README](README.md).

## Prerequisites

- **PHP 8.4 or higher** with these extensions:
  - `mbstring`, `intl`, `simplexml`, `openssl`
  - `pdo` and `pdo_mysql`
  - `gd` or `imagick` (image processing, see [docs/HEIC_SUPPORT.md](docs/HEIC_SUPPORT.md) for iPhone photos)
  - `zip` (photo and email packs)
  - `pdo_sqlite` (only to run the tests)
- **MySQL 5.7+ or MariaDB 10.3+**
- **Composer**
- **Web server** with URL rewriting (Apache or Nginx), or the PHP built-in server for development
- Optional: `gettext` (`msgfmt`) to compile the translations

Check the PHP version and extensions:

```bash
php -v
php -m | grep -E "(mbstring|intl|simplexml|pdo|openssl|gd|imagick|zip)"
```

## Installation steps

### 1. Clone the repository

```bash
git clone https://github.com/GrinwaldFlo/AngelAdmin2.git
cd AngelAdmin2
```

### 2. Install the dependencies

```bash
composer install
```

This also publishes the TinyMCE editor to `webroot/js/tinymce`, which is not in the repository. On every deployment, run `composer install` without `--no-scripts`, or run `composer run-script post-update-cmd` afterwards. If you upload the files without Composer, include `webroot/js/tinymce`, otherwise the content editor will not load.

Keep the development packages (no `--no-dev`) when you use a `.env` file: `config/bootstrap.php` loads it with `josegonzalez/dotenv`, which is a development dependency.

### 3. Create the database

```sql
CREATE DATABASE angeladmin2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'angeladmin2'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON angeladmin2.* TO 'angeladmin2'@'localhost';
FLUSH PRIVILEGES;
```

No MySQL test database is needed: the tests use SQLite (see [Running the tests](#running-the-tests)).

### 4. Configure the environment

```bash
cp .env.example .env
```

`config/app.php` reads every environment specific value from `.env` (or from real environment variables). An optional, git-ignored `config/app_local.php` can still override any key on one machine. `.env.example` lists every supported variable.

Generate a security salt. `SECURITY_SALT` is required: the application does not start without it.

```bash
openssl rand -hex 32
```

Then edit `.env`:

```bash
# Application
DEBUG=true                      # false in production
APP_MAINTENANCE=false           # true shows the maintenance page
# APP_TRUSTED_PROXIES=127.0.0.1 # behind a reverse proxy / CDN, so rate limits use the real client IP

# Security
SECURITY_SALT=the_generated_salt
# SESSION_COOKIE_SECURE=true    # force secure cookies, e.g. behind an HTTPS proxy

# Database
DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=angeladmin2
DATABASE_USER=angeladmin2
DATABASE_PASSWORD=your_secure_password
# Or a single DSN, which replaces the values above
# DATABASE_URL=mysql://user:password@localhost/angeladmin2

# Email (SMTP)
EMAIL_HOST=smtp.example.com
EMAIL_PORT=587
EMAIL_USER=your-email@example.com
EMAIL_PASSWORD=your-password
EMAIL_TLS=true

# Optional: Slack messages on the home page
# SLACK_TOKEN=
# SLACK_CHANNEL_ID=
```

Cache, log and DebugKit settings are also available, see `.env.example`.

### 5. Create the tables

```bash
bin/cake migrations migrate
```

The migrations in `config/Migrations` create the whole schema. The files in `config/schema/*.sql` are legacy upgrade scripts and are not needed for a new installation.

### 6. Set the permissions

The web server user must be able to write in these directories:

```bash
chmod -R 775 tmp/ logs/
chmod -R 775 webroot/img/
```

`webroot/img/` holds the uploaded files: member photos and documents (`img/members/`), second-hand pictures (`img/secondhand/`) and the club logo.

### 7. Start the application

Development server:

```bash
bin/cake server -p 8765
```

Then open `http://localhost:8765`. In production, use Apache or Nginx (see below).

## Web server configuration

The document root must be the `webroot/` directory.

### Apache

```apache
<VirtualHost *:80>
    ServerName angeladmin2.local
    DocumentRoot /path/to/AngelAdmin2/webroot

    <Directory /path/to/AngelAdmin2/webroot>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/angeladmin2_error.log
    CustomLog ${APACHE_LOG_DIR}/angeladmin2_access.log combined
</VirtualHost>
```

Enable `mod_rewrite`:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Nginx

```nginx
server {
    listen 80;
    server_name angeladmin2.local;
    root /path/to/AngelAdmin2/webroot;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## First setup

1. **Admin account.** The migrations don't insert any user: create the first roles and the admin user (linked to a member) directly in the database. Users who create their account from a member link get the default role (`role_id` 4).
2. **Settings.** Log in as admin and open *Configurations* to set the club name, the season (year and first day), the default language and the enabled languages.
3. **Sites and teams.** Create the sites (cities), with their IBAN and address for the QR bills, then the teams of each site.
4. **Content.** Add the texts and links shown to the members (*Contents*), the bill templates and the custom member fields (*Field types*).
5. **Branding.** Replace the logo in `webroot/img/` and the favicons, see [docs/favicon-management.md](docs/favicon-management.md).

## Updating

After pulling a new version, run:

```bash
./update.sh
```

It installs the dependencies, applies the new migrations (`--no-lock`, so the tracked schema lock file is not rewritten), and clears the caches and the schema cache. On Windows, `_update.bat` does the same.

## Production

- Set `DEBUG=false` and `CAKE_ENV=production`.
- Use HTTPS (set `SESSION_COOKIE_SECURE=true` behind a proxy that terminates TLS, and `APP_TRUSTED_PROXIES`).
- Enable OPcache, and run `composer install --optimize-autoloader`.
- Make only `tmp/`, `logs/` and `webroot/img/` writable.
- Set up log rotation, database backups and a backup of `webroot/img/`.
- Use `APP_MAINTENANCE=true` to show the maintenance page during an update.

See [docs/security.md](docs/security.md) for the full security checklist.

## Translations

Source strings are in English. The translations (French, German, Spanish, Italian) are in `resources/locales/<lang>/default.po`.

```bash
# Refresh the .pot template from the code
bin/cake i18n extract --paths src,templates --output resources/locales/src --overwrite --extract-core yes

# Compile all .po files into .mo (needs gettext)
resources/build-locales.sh
```

## Running the tests

The test suite uses [PHPUnit](https://phpunit.de/) with CakePHP's test framework. Tests live in `tests/TestCase/` and their sample data in `tests/Fixture/`.

Requirements:

- Development dependencies installed (`composer install`, without `--no-dev`)
- PHP extensions `pdo_sqlite`, `mbstring`, `intl` and `zip`
- No `.env` is needed: `phpunit.xml.dist` provides a test `SECURITY_SALT`

No MySQL server is needed: by default the `test` datasource uses a SQLite file at `tmp/tests.sqlite`. `tests/bootstrap.php` builds its tables by running the migrations in `config/Migrations`, then each test fills them with rows from its fixtures. If you add a table or a column, add a migration for it, or the tests will not see it. The tests never touch the `default` database: `tests/bootstrap.php` aliases it to the `test` connection.

To use another database, set `DATABASE_TEST_URL`:

```bash
DATABASE_TEST_URL="mysql://user:password@localhost/angeladmin2_test" composer test
```

Commands:

```bash
composer test                                                            # all tests
./vendor/bin/phpunit tests/TestCase/Controller/                          # one directory
./vendor/bin/phpunit tests/TestCase/Controller/MembersControllerTest.php # one file
./vendor/bin/phpunit --filter testIndex                                  # by name
./vendor/bin/phpunit --display-deprecations --display-phpunit-deprecations  # show deprecations
composer cs-check                                                        # code style
composer cs-fix                                                          # fix code style
composer check                                                           # tests + code style
```

Some generated tests are still marked as incomplete or skipped; they show up as `I` and `S` in the output and do not fail the run.

GitHub Actions (`.github/workflows/phpunit.yml`) runs `composer test` on PHP 8.4 for every pull request and every push to `master`.

## Troubleshooting

### Permission errors

```bash
sudo chown -R www-data:www-data /path/to/AngelAdmin2
sudo chmod -R 775 /path/to/AngelAdmin2/tmp /path/to/AngelAdmin2/logs /path/to/AngelAdmin2/webroot/img
```

### Database connection

- Check the credentials in `.env` (or `config/app_local.php`)
- Check that MySQL/MariaDB is running, that the database exists and that the user has the rights on it

### Missing PHP extensions

```bash
# Ubuntu/Debian
sudo apt-get install php8.4-mbstring php8.4-intl php8.4-xml php8.4-mysql php8.4-gd php8.4-zip php8.4-sqlite3

# Fedora/RHEL
sudo dnf install php-mbstring php-intl php-xml php-mysqlnd php-gd php-pecl-zip php-pdo
```

### The content editor does not load

`webroot/js/tinymce` is missing: run `composer run-script post-update-cmd`.

### HEIC photos are refused

See [docs/HEIC_SUPPORT.md](docs/HEIC_SUPPORT.md). `bin/cake test_heic` and `bin/cake exif_diagnostic` help to check the server.

### Log files

- `logs/error.log`: application errors
- `logs/debug.log`: debug information
- `logs/security.log`, `logs/login.log`: suspicious requests and failed logins

The admin can also read and clear these logs on the log page (`/admin/view-log`).
