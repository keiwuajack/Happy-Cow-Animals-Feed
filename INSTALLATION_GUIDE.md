# Clean Client Installation (Windows/XAMPP)

**Quick way:** install XAMPP, copy the `happy-cow-pos` folder into `C:\xampp\htdocs`, start Apache and MySQL, then double-click `INSTALL.bat`. It does steps 2 and the backup schedule automatically and prints the one-time admin password. The manual steps below are the same thing done by hand.

This procedure creates a new, empty client database. It does not copy the users, customers, sales, products, or backups from the development installation.

## 1. Prepare the client PC

- Install XAMPP with PHP 8.1 or later, Apache, and MySQL/MariaDB.
- In XAMPP, start **Apache** and **MySQL**.
- Copy the application to a simple path such as `C:\xampp\htdocs\happy-cow-pos`. Do not copy the development `backups\` folder.
- For a single-PC installation, keep Apache accessible only from the client PC. Do not expose the XAMPP server to the office network or internet.

## 2. Create the empty database

Open **Command Prompt** (not PowerShell) and run the commands below, replacing `C:\xampp` with the actual XAMPP folder if needed. Run them from the application folder:

```bat
cd /d "C:\xampp\htdocs\happy-cow-pos"
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE agrovet_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root agrovet_pos < config\schema.sql
C:\xampp\php\php.exe config\install.php
```

The first command creates a new database. If `agrovet_pos` already exists, stop and verify it is safe to use before importing anything. The schema import contains table definitions only. The installer adds the initial administrator and product categories, then prints a randomly generated, one-time administrator password. Keep that password private.

The default database connection is for a local XAMPP installation (`root` with no password). If the client has changed its MySQL credentials, set matching `POS_DB_HOST`, `POS_DB_USER`, `POS_DB_PASS`, and `POS_DB_NAME` environment variables for both the PHP command line and Apache before installation.

## 3. Sign in and configure the shop

Open `http://localhost/happy-cow-pos/login.php`, sign in as `admin` with the one-time password, and immediately change the password. Then:

- Add the client's real products, prices, and opening stock; review tax settings and receipt details.
- Create individual accounts for staff. Do not share the administrator login.
- Keep M-Pesa, email, and WhatsApp integrations disabled until the client supplies and verifies their own provider credentials.
- Confirm date, currency, and tax handling with the client before recording real sales.

## 4. Run the pre-use checks

From the application folder, run:

```bat
C:\xampp\php\php.exe -l config\install.php
C:\xampp\php\php.exe system_test.php
```

On a clean installation, no products and no sales are expected. Those are reported as warnings; missing tables, a missing administrator, or other required-check failures must be resolved before use. Also sign in through the browser and verify product entry, stock adjustment, receipt printing, and a backup.

Run `config\setup_auto_backup.bat` once (double-click) to schedule an automatic backup every day at 18:00; it also runs a test backup. Backups are kept for 30 days. Backups are stored outside the web root in `C:\xampp\pos-backups` by default. Copy backups to a second, secure location regularly and verify that a backup can be restored.

## Important: do not load development/demo data

Do not run `config\sample_data.php`, `config\add_sample_*.php`, `config\add_credit_sales.php`, `config\add_quotations.php`, `config\add_expenses.php`, or `config\add_users_roles.php` for a client installation. These scripts add sample business records; the staff-user script also contains predictable sample passwords.

The `config\schema.sql` file is a schema-only export used for clean installs. It intentionally contains no application records. Keep the existing development backup files out of the client copy.

## Scope of this setup

This setup is for one client PC only. Using the system from other PCs or over the public internet requires a separately secured LAN/HTTPS deployment; do not simply open XAMPP to inbound connections.
