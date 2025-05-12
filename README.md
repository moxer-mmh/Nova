# Nova Gaming E-commerce Website

A complete e-commerce solution for gaming equipment built with HTML, CSS, JavaScript, PHP, and MySQL.

## Table of Contents

- [Nova Gaming E-commerce Website](#nova-gaming-e-commerce-website)
  - [Table of Contents](#table-of-contents)
  - [Project Overview](#project-overview)
  - [Features](#features)
    - [Customer Features](#customer-features)
    - [Admin Features](#admin-features)
  - [Database Design](#database-design)
    - [Schema and Relationships](#schema-and-relationships)
    - [Entity-Relationship Diagram](#entity-relationship-diagram)
  - [Stored Procedures and Triggers](#stored-procedures-and-triggers)
    - [Stored Procedures](#stored-procedures)
    - [Triggers](#triggers)
  - [Installation](#installation)
    - [Prerequisites](#prerequisites)
    - [Setup Instructions](#setup-instructions)
  - [Usage](#usage)
    - [Customer Experience](#customer-experience)
    - [Admin Operations](#admin-operations)
  - [Mobile Responsiveness](#mobile-responsiveness)
  - [Default Users](#default-users)
    - [Admin Account](#admin-account)
    - [Regular User Account](#regular-user-account)
  - [Technologies Used](#technologies-used)
  - [Project Structure](#project-structure)

## Project Overview

Nova Gaming is an e-commerce platform designed for selling gaming equipment and accessories. The website provides an intuitive shopping experience for customers and a comprehensive management system for administrators. This project demonstrates the implementation of modern web development practices and database management techniques.

## Features

### Customer Features

- **User Authentication System**

  - Registration with validation
  - Login/logout functionality
  - Session management
- **Product Browsing**

  - Featured products display
  - Product categories
  - Search functionality
  - Filtering by price, category, etc.
  - Detailed product view
- **Shopping Experience**

  - Shopping cart management
  - Quantity adjustments
  - Order placement
  - Order history
  - Order cancellation
- **User Interface**

  - Responsive design (mobile, tablet, desktop)
  - Intuitive navigation
  - Clean and modern aesthetics
  - Product image galleries

### Admin Features

- **Dashboard**

  - Order statistics
  - Customer metrics
  - Product inventory overview
- **Product Management**

  - Add new products
  - Edit existing products
  - Delete products
  - Upload product images
  - Manage categories
- **Order Management**

  - View all orders
  - Update order status
  - View order details
  - Customer order history
- **User Management**

  - View all users
  - Assign admin privileges
  - View user orders

## Database Design

### Schema and Relationships

The database follows a normalized relational design with the following tables:

1. **Users**

   - `user_id` (PK): Unique identifier for each user
   - `username`: User's login name
   - `password`: Hashed password for security
   - `email`: User's email address
   - `full_name`: User's full name
   - `is_admin`: Boolean flag for admin privileges
   - `created_at`: Timestamp of account creation
2. **Products**

   - `product_id` (PK): Unique identifier for each product
   - `name`: Product name
   - `description`: Detailed product description
   - `price`: Product price
   - `stock`: Current inventory level
   - `image_url`: Path to product image
   - `category`: Product category
   - `featured`: Boolean flag for featured products
   - `created_at`: Timestamp of product creation
3. **Cart**

   - `cart_id` (PK): Unique identifier for cart item
   - `user_id` (FK): Reference to Users table
   - `product_id` (FK): Reference to Products table
   - `quantity`: Number of items in cart
   - `added_at`: Timestamp when added to cart
4. **Orders**

   - `order_id` (PK): Unique identifier for each order
   - `user_id` (FK): Reference to Users table
   - `order_date`: Date and time of order
   - `total_amount`: Total cost of order
   - `status`: Current status (Pending, Processing, Shipped, etc.)
5. **Order_Items**

   - `order_id` (FK): Reference to Orders table
   - `product_id` (FK): Reference to Products table
   - `quantity`: Number of items ordered
   - `price_per_unit`: Price at time of purchase
6. **Cancelled_Orders**

   - `cancel_id` (PK): Unique identifier for cancelled order
   - `order_id`: Reference to the original order
   - `user_id`: Reference to the user
   - `cancel_date`: Date and time of cancellation
   - `total_amount`: Total cost of cancelled order
   - `reason`: Optional reason for cancellation

### Entity-Relationship Diagram

```
Users (1) ------ (*) Orders
                   |
                   |
                   (*) 
Products (*) ---- (*) Order_Items
   |
   |
   (*) 
Cart (*) ---- (1) Users
```

## Stored Procedures and Triggers

### Stored Procedures

1. **GetOrderDetails**

   - Purpose: Retrieves detailed information about a specific order
   - Parameters: `p_order_id` (Order ID)
   - Returns: Complete order information including products, prices, and totals
2. **FinalizeOrder**

   - Purpose: Processes a new order from the user's cart
   - Parameters: `p_user_id` (User ID), `p_order_id` (OUTPUT parameter)
   - Actions: Creates order, transfers cart items to order items, updates product stock, empties cart
3. **GetOrderHistory**

   - Purpose: Retrieves order history for a specific user
   - Parameters: `p_user_id` (User ID)
   - Returns: List of orders with basic information

### Triggers

1. **before_cart_insert**

   - Purpose: Prevents adding products to cart when stock is insufficient
   - Timing: BEFORE INSERT on Cart table
   - Action: Checks product stock and rejects if quantity exceeds available stock
2. **before_cart_update**

   - Purpose: Validates cart quantity updates
   - Timing: BEFORE UPDATE on Cart table
   - Action: Checks product stock and rejects if updated quantity exceeds available stock
3. **after_order_status_update**

   - Purpose: Handles order cancellation process
   - Timing: AFTER UPDATE on Orders table
   - Action: If status changed to 'Cancelled', restores product stock and logs to Cancelled_Orders

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

### Setup Instructions

1. **Clone the repository**

   ```
   git clone https://github.com/yourusername/Nova.git
   ```
2. **Set up the database**

   - Create a MySQL database
   - Import the database schema by running the `install_db.sql` script

   ```
   mysql -u username -p your_database < database/install_db.sql
   ```
3. **Configure database connection**

   - Open `includes/db.php` and update the database credentials:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'nova_ecommerce');
   ```
4. **Set up the web server**

   - Configure your web server to point to the project directory
   - Ensure the server has write permissions to the `assets/images` directory
5. **Access the website**

   - Customer site: `http://your-domain/index.php`
   - Admin panel: `http://your-domain/admin/index.php`

## Usage

### Customer Experience

1. **Browse Products**

   - View featured products on the homepage
   - Browse categories
   - Use search functionality to find specific products
   - Filter products by price, category, etc.
2. **Shopping**

   - View detailed product information
   - Add products to cart
   - Manage cart (update quantities, remove items)
   - Proceed to checkout
3. **Account Management**

   - Register for a new account
   - Login/logout
   - View order history
   - Cancel pending orders

### Admin Operations

1. **Dashboard**

   - View sales statistics
   - Monitor new orders
   - Track product inventory levels
2. **Product Management**

   - Add new products with images and descriptions
   - Update existing product information
   - Delete products
   - Manage product categories
3. **Order Management**

   - View all orders
   - Update order status (Processing, Shipped, Delivered, etc.)
   - View detailed order information
4. **User Management**

   - View all registered users
   - Assign or revoke admin privileges
   - View a specific user's order history

## Mobile Responsiveness

Nova Gaming is fully responsive and optimized for all devices:

- **Mobile Optimizations**

  - Collapsible navigation menu
  - Adapted layouts for small screens
  - Touch-friendly inputs and buttons
  - Card-based responsive tables
- **Tablet Optimizations**

  - Adjusted grid layouts
  - Responsive product displays
  - Optimized forms and checkout process
- **Desktop Experience**

  - Multi-column layouts
  - Enhanced product galleries
  - Comprehensive admin dashboard

## Default Users

### Admin Account

- Username: admin
- Password: admin123

## Technologies Used

- **Frontend**:

  - HTML5
  - CSS3
  - JavaScript
- **Backend**:

  - PHP
  - MySQL
- **Additional Techniques**:

  - Responsive design
  - Session management
  - Password hashing
  - Prepared statements for SQL security

## Project Structure

```
Nova/
│
├── admin/                  # Admin panel files
│   ├── add_product.php     # Add new product form
│   ├── edit_product.php    # Edit product form
│   ├── index.php           # Admin dashboard
│   ├── manage_orders.php   # Order management
│   ├── manage_products.php # Product management
│   ├── manage_users.php    # User management
│   ├── view_order.php      # Order details
│   └── view_user_orders.php # User order history
│
├── assets/                 # Static assets
│   ├── css/                # Stylesheets
│   │   └── style.css       # Main CSS file
│   ├── images/             # Product and site images
│   └── js/                 # JavaScript files
│       └── script.js       # Main JS file
│
├── database/               # Database files
│   └── install_db.sql      # Database schema and sample data
│
├── includes/               # Shared PHP components
│   ├── currency_format.php # Currency formatting functions
│   ├── db.php              # Database connection
│   ├── footer.php          # Common footer
│   └── header.php          # Common header with navigation
│
├── add_to_cart.php         # Add product to cart functionality
├── cancel_order.php        # Order cancellation functionality
├── cart.php                # Shopping cart page
├── checkout.php            # Checkout process
├── index.php               # Homepage
├── login.php               # User login page
├── logout.php              # Logout functionality
├── order_confirmation.php  # Order confirmation page
├── order_details.php       # Detailed view of an order
├── orders.php              # Order history page
├── product_details.php     # Single product view
├── products.php            # All products listing with filters
├── register.php            # User registration page
└── README.md               # Project documentation
```
