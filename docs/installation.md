# Installation Guide

This guide will help you install and set up AngelAdmin2 on your local development environment or production server.

## Prerequisites

### System Requirements
- **PHP 8.1 or higher** with the following extensions:
  - mbstring
  - intl
  - simplexml
  - PDO
  - pdo_mysql
  - openssl
  - gd or imagick (for image processing)
- **MySQL 5.7+ or MariaDB 10.3+**
- **Composer** (PHP package manager)
- **Web server** (Apache, Nginx, or PHP built-in server for development)

### Check PHP Version and Extensions
```bash
# Check PHP version
php -v

# Check installed extensions
php -m

# Check if required extensions are loaded
php -m | grep -E "(mbstring|intl|simplexml|pdo|openssl|gd)"
```

## Installation Steps

### 1. Clone the Repository
```bash
git clone https://github.com/GrinwaldFlo/AngelAdmin2.git
cd AngelAdmin2
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Database Setup

#### Create Database
```sql
-- Connect to MySQL/MariaDB as root or admin user
CREATE DATABASE angeladmin2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create a user for the application (recommended)
CREATE USER 'angeladmin2'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON angeladmin2.* TO 'angeladmin2'@'localhost';
FLUSH PRIVILEGES;
```

#### Create Test Database (for development)
```sql
CREATE DATABASE angeladmin2_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON angeladmin2_test.* TO 'angeladmin2'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Configuration

#### Copy Configuration Files
```bash
# Copy the example environment file
cp .env.example .env

# Copy the local configuration file
cp config/app_local.example.php config/app_local.php
```

#### Generate Security Salt
```bash
# Generate a random 32-character string
openssl rand -base64 32
```

#### Edit Configuration
Update `.env` file with your settings:
```bash
# Edit the .env file
nano .env
```

Key settings to update:
- `SECURITY_SALT`: Use the generated salt from above
- Database connection details
- Email configuration (if needed)

### 5. Database Migrations
```bash
# Run database migrations to create tables
bin/cake migrations migrate

# (Optional) Seed the database with initial data
bin/cake migrations seed
```

### 6. Set Permissions
```bash
# Make sure these directories are writable
chmod -R 755 tmp/
chmod -R 755 logs/
chmod -R 755 webroot/img/
chmod -R 755 webroot/uploads/

# On some systems you might need 777
# chmod -R 777 tmp/ logs/
```

### 7. Start the Application

#### Development Server
```bash
# Start the built-in PHP server
bin/cake server -p 8765

# Access the application at: http://localhost:8765
```

#### Apache/Nginx Configuration
See the "Web Server Configuration" section below.

## Web Server Configuration

### Apache Configuration
Create a virtual host or configure your document root:

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

Make sure mod_rewrite is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Nginx Configuration
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
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Initial Setup

### Create Admin User
After installation, you'll need to create an initial admin user through the database or via a seeder.

### Configure Application Settings
1. Log in to the admin panel
2. Navigate to Settings/Configuration
3. Set up:
   - Site information
   - Email templates
   - Season dates
   - Language preferences
   - Logo and branding

## Troubleshooting

### Common Issues

#### Permission Errors
```bash
# Fix directory permissions
sudo chown -R www-data:www-data /path/to/AngelAdmin2
sudo chmod -R 755 /path/to/AngelAdmin2
sudo chmod -R 777 /path/to/AngelAdmin2/tmp
sudo chmod -R 777 /path/to/AngelAdmin2/logs
```

#### Database Connection Issues
- Verify database credentials in `.env` or `config/app_local.php`
- Check if MySQL/MariaDB is running
- Verify the database exists and user has proper permissions

#### Missing PHP Extensions
```bash
# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php8.1-mbstring php8.1-intl php8.1-xml php8.1-mysql php8.1-gd

# For CentOS/RHEL
sudo yum install php-mbstring php-intl php-xml php-mysqlnd php-gd
```

#### Composer Issues
```bash
# Update Composer
composer self-update

# Clear Composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor/
composer install
```

### Log Files
Check these log files for error information:
- `logs/error.log` - Application errors
- `logs/debug.log` - Debug information
- Web server error logs

### Getting Help
- Check the main README.md file
- Create an issue on GitHub
- Review the CakePHP documentation at https://book.cakephp.org/

## Production Deployment

For production deployment, additional considerations:

1. **Security**:
   - Set `DEBUG=false`
   - Use HTTPS
   - Secure file permissions
   - Regular security updates

2. **Performance**:
   - Enable opcache
   - Configure caching
   - Optimize database
   - Use a proper web server

3. **Monitoring**:
   - Set up log rotation
   - Monitor disk space
   - Database backups
   - Application monitoring

See the production deployment section in the main README for detailed instructions.
