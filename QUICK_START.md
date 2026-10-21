# Quick Start

For the client’s single-PC, offline XAMPP installation, follow [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md). It is the current setup procedure.

## First start

1. Install XAMPP with Apache, MySQL/MariaDB, and PHP 8.1 or later.
2. Copy this folder into XAMPP's `htdocs` directory.
3. Start Apache and MySQL in the XAMPP Control Panel.
4. Run `INSTALL.bat`. It creates a new `agrovet_pos` database, imports the schema, creates the administrator, and schedules local backups. It stops if that database already exists.
5. Write down the one-time administrator password shown by the installer. Sign in at `http://localhost/<folder-name>/login.php` and change that password immediately.
6. Enter the client's products, prices, opening stock, business details, and staff accounts before recording real transactions.

The administrator password is generated during installation. There is no shared default password.

## Offline use

The POS pages and their icon, chart, and PDF libraries are stored in this project and load from localhost. Core sales and inventory work against the local XAMPP database.

M-Pesa STK push, email, SMS, and WhatsApp delivery contact external providers and require internet access and provider credentials. Leave those integrations disabled on an offline installation.

## Backups

The installer schedules a daily local database backup. Backups are stored outside the web root under the XAMPP directory. Copy them to a separate secure drive regularly and verify that a restore works.

Do not import development backups or run sample-data scripts on the client's database. For an older product schema, use only the additive product-field migration documented in [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md), after backing up the database.
