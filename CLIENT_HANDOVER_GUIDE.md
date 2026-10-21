# Happy Cow Feeds System
## Client Quick Guide

### About the system

Happy Cow Feeds is a local sales and business management system. It runs on this computer through XAMPP. The core features work without an internet connection.

### Start the system

1. Open **XAMPP Control Panel**.
2. Start **Apache** and **MySQL**.
3. Open a browser and go to `http://localhost/Happy%20Cow%20Feeds/login.php`.
4. Sign in with the username and password provided by the administrator.

Keep account passwords private. An administrator can create staff accounts and limit which sections each person can access.

### Main tasks

- Record sales, payments, and any discount given during checkout.
- Add and review products, stock, and stock adjustments.
- Manage customers, suppliers, quotations, and returns.
- Open and close cashier shifts, then review cash and M-Pesa totals.
- View reports and manage staff access, if your account allows it.

### Protect business data

- Back up the database regularly in **phpMyAdmin**: select the `agrovet_pos` database, choose **Export**, keep the **Quick** and **SQL** options, then select **Go**. Store the downloaded file on a separate USB drive or other safe location.
- Also copy the `Happy Cow Feeds` project folder from XAMPP's `htdocs` folder. Keep the folder copy and database export together, and keep more than one backup.
- Do not run the installer or import a new database schema on the working system. This can replace or disrupt existing business data.
- Do not delete the project folder or its `uploads` folder; they may contain business files and product images.

### Internet and shutdown

Sales, stock, customers, shifts, and reports run locally. Online services such as M-Pesa STK payments, email, or WhatsApp require an internet connection and valid service settings.

When finished, close the application and stop **Apache** and **MySQL** from XAMPP Control Panel. Do not switch off the computer while a sale or backup is still being saved.

### If there is a problem

Check that Apache and MySQL are running in XAMPP, then refresh the browser. If the system still does not open, ask the system administrator for help. Do not reinstall XAMPP or reset the database before making a backup.
