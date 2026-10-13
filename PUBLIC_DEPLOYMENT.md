# Public Deployment Checklist

This application is plain PHP for Apache and MySQL/MariaDB. Do not use `php artisan serve`.

## Server configuration

1. Configure PHP 8.1+ with `mysqli`, `PDO_MySQL`, `curl`, and `fileinfo` enabled.
2. Point Apache's document root at this project directory.
3. Enable `mod_rewrite`, `mod_headers`, and `mod_alias`.
4. Allow the project `.htaccess` files to override `Indexes`, `FileInfo`, and `Limit` settings.
5. Set the variables in `.env.example` as real server environment variables. Do not commit secrets.
6. Create a dedicated database user with only this application's database permissions. Do not use MySQL `root`.
7. Set `POS_BACKUP_DIR` to a directory outside the document root and make it writable by the web-server account.
8. Set `POS_MYSQLDUMP_PATH` to the actual `mysqldump` path on the server.
9. Enable HTTPS at the hosting provider, then uncomment the HSTS and HTTP-to-HTTPS rules in `.htaccess`.
10. Ensure the web-server account cannot write PHP files or application source code.
11. Remove or quarantine installation and maintenance scripts after setup.

## First login

- Change every existing user password before opening the system publicly.
- Confirm administrator, manager, and cashier roles behave as intended.
- Confirm the login page no longer displays a default password.

## Verification

Run from the project directory:

```text
php -l system_test.php
php system_test.php
```

Then verify manually over HTTPS:

- Login and logout
- Open a cashier shift before completing a sale, then close it and verify the variance
- Product, customer, and stock updates
- Sale creation and receipt download
- Quotation conversion and returns
- Backup creation, download, deletion, and restore
- Access to `/config/`, `/includes/`, and `/backups/` returns 404/403
- HTTP redirects to HTTPS after enabling the rules
