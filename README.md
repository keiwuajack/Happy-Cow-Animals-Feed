# Happy Cow Feeds POS

A point of sale and inventory system for animal-feed and agricultural supply businesses.

## Features

### Core Modules
- **Point of Sale (POS)** - Fast and intuitive sales interface
- **Product Management** - Complete inventory control
- **Customer Management** - Track farmers, dealers, and individual customers
- **Sales History** - Detailed transaction records
- **Reports & Analytics** - Business insights and performance tracking
- **Categories Management** - Organize products by type
- **Suppliers Management** - Vendor information tracking

### Key Capabilities
- Multi-user support with role-based access (Admin, Manager, Cashier)
- Real-time inventory tracking
- Low stock alerts
- Multiple payment methods (Cash, M-Pesa, Card, Credit)
- Receipt printing
- Tax calculation (16% VAT)
- Discount management
- Customer credit limits
- Detailed sales reports
- Profit & loss analysis

## Installation

Follow [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md) for a clean Windows/XAMPP installation. The included schema contains table definitions only; it does not include the current local users, sales, or inventory.

The installer creates a one-time administrator password in the command-line output. Change it after the first login.

## Technology Stack
- **Backend:** PHP, MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Database:** MySQL/MariaDB
- **Server:** Apache (XAMPP)

## Database Structure

### Tables
- `users` - System users and authentication
- `products` - Product inventory
- `categories` - Product categories
- `customers` - Customer information
- `suppliers` - Supplier details
- `sales` - Sales transactions
- `sale_items` - Individual sale line items

## Default Product Categories
- Seeds & Planting
- Fertilizers
- Pesticides
- Animal Feed
- Veterinary Supplies
- Farm Tools
- Irrigation

## Usage

### Making a Sale
1. Navigate to Point of Sale
2. Search or select products
3. Adjust quantities as needed
4. Select customer (optional)
5. Apply discount (if applicable)
6. Choose payment method
7. Complete sale and print receipt

### Managing Products
1. Go to Products page
2. Add new products with SKU, pricing, and stock levels
3. Set reorder levels for low stock alerts
4. Assign categories and units

### Viewing Reports
1. Access Reports & Analytics
2. Select report type and date range
3. Generate and view detailed insights
4. Export to CSV for further analysis

## Security Features
- Session-based authentication
- Role-based access control
- SQL injection protection
- Password hashing (bcrypt)

## Support
For issues or questions, please contact the system administrator.

---
Built for agricultural and veterinary businesses in Kenya
