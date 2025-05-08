# Nova Gaming E-commerce Website

A complete e-commerce solution for gaming equipment built with HTML, CSS, JavaScript, PHP, and MySQL.

## Features

### Customer Features
- User registration and authentication
- Browse products by category
- Search functionality
- Product filtering by price and category
- Shopping cart system
- Order management
- Order history view
- Order cancellation

### Admin Features
- Dashboard with statistics
- Product management (add, edit, delete)
- Image upload for products
- Order management
- User management

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

### Setup Instructions

1. **Set up the database**
   - Create a MySQL database
   - Import the database schema by running the `install_db.sql` script
   ```
   mysql -u username -p < install_db.sql
   ```

2. **Configure database connection**
   - Open `includes/db.php` and update the database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root'); // Change to your MySQL username
   define('DB_PASS', ''); // Change to your MySQL password
   define('DB_NAME', 'nova_ecommerce');
   ```

3. **Set up the web server**
   - Configure your web server to point to the project directory
   - Ensure the server has write permissions to the `assets/images` directory for product image uploads

4. **Access the website**
   - Customer site: `http://your-domain/index.php`
   - Admin panel: `http://your-domain/admin/index.php`

## Default Users

### Admin Account
- Username: admin
- Password: admin123

### Regular User Account
- Username: user
- Password: user123

## Project Structure

```
Nova/
│
├── admin/                  # Admin panel files
│   ├── add_product.php     # Add new product form
│   ├── edit_product.php    # Edit product form
│   ├── index.php           # Admin dashboard
│   └── products.php        # Product management
│
├── assets/                 # Static assets
│   ├── css/                # Stylesheets
│   │   └── style.css      
│   ├── images/             # Product and site images
│   └── js/                 # JavaScript files
│       └── script.js
│
├── includes/               # Shared PHP components
│   ├── db.php              # Database connection
│   ├── footer.php          # Common footer
│   └── header.php          # Common header with navigation
│
├── add_to_cart.php         # Add product to cart functionality
├── cancel_order.php        # Order cancellation functionality
├── cart.php                # Shopping cart page
├── checkout.php            # Checkout process
├── database.sql            # Database schema (duplicate of install_db.sql)
├── index.php               # Homepage
├── install_db.sql          # Database installation script
├── login.php               # User login page
├── logout.php              # Logout functionality
├── order_confirmation.php  # Order confirmation page
├── order_details.php       # Detailed view of an order
├── orders.php              # Order history page
├── product_details.php     # Single product view
├── products.php            # All products listing with filters
├── README.md               # Project documentation
└── register.php            # User registration page
```

## Additional Development

If you want to expand this e-commerce website, consider adding the following features:

1. User profile management
2. Product reviews and ratings
3. Wishlist functionality
4. Discount coupon system
5. Integrated payment gateway
6. Email notifications
7. Advanced product search with filters
8. Multi-language support

## Credits

This project was developed as a custom e-commerce solution for Nova Gaming.