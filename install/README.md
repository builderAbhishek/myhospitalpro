# My Hospital Pro (MHP) — Installation Guide

## Quick Start via PHP Built-in Server

1. **Verify PHP Installation**
   Ensure PHP 8.0 or newer is installed with `pdo_mysql` extension.
   ```bash
   php -v
   ```

2. **Database Import**
   Create a MySQL database named `mhp_database` and import the schema:
   ```bash
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mhp_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p mhp_database < database/mhp_database.sql
   ```

3. **Configure Database Connection**
   Edit `config.php` if your MySQL username, password, or port differs from defaults:
   ```php
   'db' => [
       'host' => 'localhost',
       'port' => 3306,
       'name' => 'mhp_database',
       'user' => 'root',
       'pass' => '',
       'charset' => 'utf8mb4',
   ],
   ```

4. **Launch Server**
   ```bash
   php -S localhost:8000
   ```

5. **Login**
   - URL: http://localhost:8000
   - Username: `admin`
   - Password: `admin123`
