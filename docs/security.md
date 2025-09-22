# Security Guide

This document outlines security considerations and best practices for deploying and maintaining AngelAdmin2.

## Table of Contents
- [Security Features](#security-features)
- [Production Security Checklist](#production-security-checklist)
- [Authentication & Authorization](#authentication--authorization)
- [Data Protection](#data-protection)
- [Common Vulnerabilities](#common-vulnerabilities)
- [Security Monitoring](#security-monitoring)
- [Incident Response](#incident-response)

## Security Features

AngelAdmin2 includes several built-in security features:

### Authentication Systems
1. **Traditional Authentication**: Username/password with session management
2. **Hash-based Authentication**: Secure token-based access for members
3. **Role-based Access Control**: Granular permissions system

### Data Protection
- Password hashing using PHP's secure algorithms
- CSRF protection for forms
- SQL injection prevention through ORM
- XSS protection via output escaping
- Secure session configuration

### Security Headers
- Configurable session security settings
- HTTP-only cookies
- Secure cookie flags for HTTPS

## Production Security Checklist

### Environment Configuration
- [ ] **Disable Debug Mode**: Set `DEBUG=false` in production
- [ ] **Secure Salt**: Use a strong, unique `SECURITY_SALT`
- [ ] **Environment Variables**: Store sensitive data in environment variables
- [ ] **Remove Dev Dependencies**: Run `composer install --no-dev`

### Web Server Security
- [ ] **HTTPS Only**: Force HTTPS for all connections
- [ ] **Security Headers**: Implement proper HTTP security headers
- [ ] **Hide Server Info**: Disable server version disclosure
- [ ] **Directory Browsing**: Disable directory listing

#### Example Security Headers (Apache)
```apache
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Content-Security-Policy "default-src 'self'"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

#### Example Security Headers (Nginx)
```nginx
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options DENY;
add_header X-XSS-Protection "1; mode=block";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
add_header Content-Security-Policy "default-src 'self'";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

### File System Security
- [ ] **Proper Permissions**: Set restrictive file permissions
- [ ] **Writable Directories**: Only make necessary directories writable
- [ ] **Remove Sensitive Files**: Remove `.env.example`, test files in production

#### Recommended File Permissions
```bash
# Application files (read-only)
find /path/to/angeladmin2 -type f -exec chmod 644 {} \;
find /path/to/angeladmin2 -type d -exec chmod 755 {} \;

# Writable directories
chmod 755 tmp/ logs/ webroot/uploads/

# Configuration files (more restrictive)
chmod 600 config/app_local.php .env
```

### Database Security
- [ ] **Dedicated User**: Create a specific database user for the application
- [ ] **Minimal Privileges**: Grant only necessary database permissions
- [ ] **Connection Security**: Use SSL for database connections if possible
- [ ] **Regular Backups**: Implement automated, secure backups

#### Database User Setup
```sql
-- Create dedicated user with minimal privileges
CREATE USER 'angeladmin2_prod'@'localhost' IDENTIFIED BY 'strong_random_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON angeladmin2.* TO 'angeladmin2_prod'@'localhost';
FLUSH PRIVILEGES;
```

## Authentication & Authorization

### Password Policies
AngelAdmin2 uses CakePHP's built-in password hashing:
- Passwords are hashed using `password_hash()` with `PASSWORD_DEFAULT`
- No plain text passwords are stored
- Session tokens are regenerated on login

### Hash-based Authentication
For member access without passwords:
- Uses SHA-1 hashes for member identification
- Secure cookie implementation with proper flags
- 24-hour expiration for hash-based sessions
- Validation against database-stored hashes

### Session Security
Configure secure sessions in `config/app.php`:
```php
'Session' => [
    'timeout' => 30240, // 21 days in minutes
    'cookieTimeout' => 1814400, // 21 days in seconds
    'ini' => [
        'session.cookie_lifetime' => 1814400,
        'session.cookie_secure' => true, // HTTPS only
        'session.cookie_httponly' => true, // Prevent XSS
        'session.cookie_samesite' => 'Lax',
        'session.use_strict_mode' => true,
        'session.use_only_cookies' => true,
    ],
],
```

## Data Protection

### Personal Data Handling
AngelAdmin2 handles sensitive member data:
- Personal information (names, addresses, dates of birth)
- Contact details (email, phone numbers)
- Financial information (billing, payments)
- Photos and documents

### GDPR Compliance Considerations
- Implement data retention policies
- Provide data export functionality
- Enable data deletion capabilities
- Maintain audit logs for data access
- Obtain proper consent for data processing

### File Upload Security
```php
// Example validation for image uploads
'photo' => [
    'upload' => [
        'rule' => ['uploadedFile', [
            'types' => ['image/jpeg', 'image/png'],
            'maxSize' => '2MB'
        ]],
        'message' => 'Invalid file upload'
    ]
]
```

## Common Vulnerabilities

### SQL Injection Prevention
AngelAdmin2 uses CakePHP's ORM which provides automatic protection:
```php
// Safe: Uses parameterized queries
$members = $this->Members->find()
    ->where(['active' => 1])
    ->where(['team_id' => $teamId]);

// Avoid: Raw SQL without proper escaping
// $query = "SELECT * FROM members WHERE team_id = " . $teamId; // DON'T DO THIS
```

### Cross-Site Scripting (XSS) Prevention
```php
// Safe: Automatic escaping in templates
echo h($member->first_name);

// Safe: Using Html helper
echo $this->Html->link($member->name, ['action' => 'view', $member->id]);

// Raw output only when necessary and trusted
echo $this->Text->autoParagraph($content); // Only for admin content
```

### Cross-Site Request Forgery (CSRF) Protection
```php
// Enable CSRF protection in forms
$this->loadComponent('FormProtection', [
    'unlockedFields' => ['dynamic_field']
]);
```

### File Upload Vulnerabilities
```php
// Validate file types and sizes
$validator
    ->add('photo', 'file', [
        'rule' => ['mimeType', ['image/jpeg', 'image/png']],
        'message' => 'Only JPEG and PNG images allowed'
    ])
    ->add('photo', 'filesize', [
        'rule' => ['fileSize', '<=', '2MB'],
        'message' => 'File too large'
    ]);
```

## Security Monitoring

### Log Security Events
Configure security logging in `config/app.php`:
```php
'Log' => [
    'security' => [
        'className' => 'Cake\Log\Engine\FileLog',
        'path' => LOGS,
        'file' => 'security',
        'scopes' => ['security'],
        'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
    ],
]
```

### Monitor for Suspicious Activity
- Failed login attempts
- Unusual access patterns
- Large data exports
- Administrative actions
- File upload attempts

### Example Security Logging
```php
// Log security events
use Cake\Log\Log;

// Failed login attempt
Log::write('warning', 'Failed login attempt for: ' . $username, ['scope' => ['security']]);

// Successful admin action
Log::write('info', 'User ' . $user->username . ' deleted member ID: ' . $memberId, ['scope' => ['security']]);
```

### Regular Security Tasks
- [ ] **Update Dependencies**: Regular `composer update`
- [ ] **Review Logs**: Monitor security and error logs
- [ ] **Backup Verification**: Test backup restoration
- [ ] **Access Review**: Audit user accounts and permissions
- [ ] **Vulnerability Scanning**: Use tools like `composer audit`

## Incident Response

### Security Incident Procedure
1. **Immediate Response**
   - Isolate affected systems
   - Preserve evidence
   - Document the incident
   - Notify stakeholders

2. **Investigation**
   - Analyze logs
   - Determine scope of compromise
   - Identify attack vectors
   - Assess data impact

3. **Recovery**
   - Patch vulnerabilities
   - Restore from clean backups
   - Reset compromised credentials
   - Update security measures

4. **Post-Incident**
   - Document lessons learned
   - Update security procedures
   - Implement additional monitoring
   - Communicate with affected parties

### Emergency Contacts
Maintain a list of emergency contacts:
- System administrators
- Database administrators
- Security team
- Legal counsel (for data breaches)
- Key stakeholders

## Security Tools and Resources

### Recommended Security Tools
- **Composer Audit**: `composer audit` for dependency vulnerabilities
- **PHPStan**: Static analysis for code quality
- **SonarQube**: Code security analysis
- **OWASP ZAP**: Web application security testing

### Security Resources
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CakePHP Security Guidelines](https://book.cakephp.org/4/en/security.html)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)

## Reporting Security Issues

**Please do not report security vulnerabilities in public GitHub issues.**

To report security vulnerabilities:
1. Email: security@example.com (replace with actual contact)
2. Include detailed description
3. Provide steps to reproduce
4. Allow reasonable time for response

We will:
- Acknowledge receipt within 48 hours
- Provide regular updates on progress
- Credit reporters (unless requested otherwise)
- Coordinate disclosure timing
