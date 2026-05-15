# Installation Guide

Setting up NutriMax locally requires a standard LAMP/XAMPP stack.

## Prerequisites

- **PHP 8.0+**
- **MySQL 5.7+** or **MariaDB**
- **Apache** (with `mod_rewrite` enabled) or Nginx
- Modern Web Browser

## Step-by-Step Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Agustin-Aurellana/NutriMax.git
   cd NutriMax
   ```

2. **Database Setup**
   - Create a new MySQL database named `nutrimax`.
   - Import the SQL schema located in the `/sql` directory into your database.

3. **Environment Configuration**
   - Duplicate or configure your connection file (e.g. `config/conexion.php` or using environment variables if supported).
   - Update your MySQL credentials:
     ```php
     $_ENV['DB_HOST'] = 'localhost';
     $_ENV['DB_USER'] = 'root';
     $_ENV['DB_PASS'] = '';
     $_ENV['DB_NAME'] = 'nutrimax';
     ```

4. **Web Server Configuration**
   - Point your local web server's document root to the `public/` directory, OR ensure requests to the NutriMax folder correctly hit `public/index.php`.
   - Ensure `.htaccess` processing is enabled if using Apache (AllowOverride All).

5. **Run the Application**
   - Open `http://localhost/NutriMax/public/` (or your configured local domain) in your browser.

---
 [← Back to README](../../README.md)
