# Vulnerable Job Portal Application

This is a PHP job portal application intentionally designed with various security vulnerabilities for educational and security testing purposes

## Features

### Authentication
- User registration with email verification (using Mailtrap)
- JWT-based authentication with exposed secrets
- Role-based access control (Member/Company)

### Member Features
- Dashboard with application statistics
- Profile management with photo upload
- CV upload and management
- Job search and application
- Application history

### Company Features
- Company dashboard
- Job posting and management (CRUD)
- Applicant management
- Application review system

## Security Vulnerabilities (Intentional)

### 1. SQL Injection
- Direct SQL queries without prepared statements
- User input directly concatenated into queries
- No input sanitization

### 2. Cross-Site Scripting (XSS)
- Direct HTML output without escaping
- innerHTML assignments with user data
- Reflected XSS in search functionality

### 3. Broken Authentication
- Weak JWT implementation
- No signature verification
- Secrets exposed in client-side code
- Session management vulnerabilities

### 4. Broken Access Control
- Missing authorization checks
- Role-based access control bypass
- Insecure direct object references

### 5. File Upload Vulnerabilities
- No file type validation
- No file size limits
- Path traversal vulnerabilities
- Dangerous file types allowed (.php, .exe)

### 6. Sensitive Data Exposure
- Database credentials in plain text
- User data logged in console
- Sensitive information in client-side code

### 7. Cross-Site Request Forgery (CSRF)
- No CSRF tokens
- State-changing operations without protection

### 8. Insecure File Operations
- Path traversal in file inclusion
- Unrestricted file deletion
- Directory traversal vulnerabilities

## Installation

### Using Docker

1. Clone the repository
2. Build and run with Docker Compose:
   ```bash
   docker-compose up -d
   ```

3. Access the application:
   - Main app: http://localhost:8004
   - phpMyAdmin: http://localhost:9004

### Manual Installation

1. Requirements:
   - PHP 8.0+
   - MySQL 8.0+
   - Apache/Nginx

2. Database setup:
   ```sql
   CREATE DATABASE job_portal;
   ```

3. Import the database schema from `database/init.sql`

4. Configure environment variables in `config/env.php`

5. Set up Mailtrap account for email testing

## Default Credentials

- **Admin**: admin@example.com / admin123
- **Member**: member1@example.com / password123  
- **Company**: company1@example.com / company123

## File Structure

```
├── config/
│   ├── env.php (Environment configuration)
│   └── database.php (Database connection)
├── includes/
│   ├── auth.php (Authentication logic)
│   ├── jwt.php (JWT handling)
│   └── file_upload.php (File upload handling)
├── templates/
│   ├── header.php
│   ├── nav.php
│   └── footer.php
├── pages/
│   ├── auth/
│   │   ├── register.php
│   │   ├── login.php
│   │   └── logout.php
│   ├── member/
│   │   ├── dashboard.php
│   │   ├── profile.php
│   │   ├── cv.php
│   │   ├── jobs.php
│   │   └── history.php
│   └── company/
│       ├── dashboard.php
│       ├── jobs.php
│       └── applicants.php
├── uploads/ (File upload directory)
├── database/
│   └── init.sql (Database schema)
├── docker-compose.yml
├── Dockerfile
└── index.php
```

## Security Testing

This application can be used to test various security scanning tools and manual penetration testing techniques:

- **SQLMap** for SQL injection testing
- **Burp Suite** for web application security testing
- **OWASP ZAP** for automated security scanning
- **XSStrike** for XSS testing
- **Nikto** for web server scanning

## Educational Purpose

This application is designed for:
- Security training and education
- Penetration testing practice
- Security tool validation
- Demonstrating common web vulnerabilities

## Warning

⚠️ **DO NOT deploy this application to production or any public environment!** 

This application contains intentional security vulnerabilities and should only be used in isolated, controlled environments for educational purposes.

## Learning Resources

- OWASP Top 10: https://owasp.org/www-project-top-ten/
- Web Application Security Testing: https://owasp.org/www-project-web-security-testing-guide/
- PHP Security: https://phpsecurity.readthedocs.io/

## License

This project is for educational purposes only. Use responsibly and ethically.