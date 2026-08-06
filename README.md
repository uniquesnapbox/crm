# USB CRM - E-Learning CRM

A comprehensive Laravel-based e-learning CRM system built with modern web technologies.

## Table of Contents

- [System Requirements](#system-requirements)
- [Installation Steps](#installation-steps)
- [Configuration](#configuration)
- [Post-Installation](#post-installation)
- [Plugins Used](#plugins-used)

## System Requirements

### Server Requirements

- **PHP**: 8.1.0 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache (with mod_rewrite) or Nginx
- **Composer**: Latest version

### Required PHP Extensions

- **PDO** - Database abstraction layer
- **Tokenizer** - Token parsing
- **cURL** - HTTP client
- **OpenSSL** - Encryption support
- **MBString** - Multibyte string handling
- **GD** - Image processing
- **Zip** - Archive handling
- **Intl** - Internationalization
- **Ctype** - Character type checking

### PHP Configuration

- **max_execution_time**: Greater than 30 seconds
- **upload_max_filesize**: At least 20MB
- **post_max_size**: At least 20MB
- **allow_url_fopen**: Enabled
- **proc_open**: Enabled
- **proc_close**: Enabled

### Server Modules

- **mod_rewrite**: Enabled (for Apache)

## Installation Steps

### Step 1: Clone or Download the Project

```bash
# If using Git
git clone <repository-url> e-learning-crm
cd e-learning-crm

# Or extract the downloaded ZIP file to your web server directory
```

### Step 2: Install PHP Dependencies

Make sure you have Composer installed. If not, install it from [getcomposer.org](https://getcomposer.org/).

```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader
```

For development environment:
```bash
composer install
```

### Step 3: Environment Configuration

1. Copy the environment example file:
```bash
cp .env.example .env
```

2. Generate application key:
```bash
php artisan key:generate
```

3. Edit the `.env` file with your configuration:
```bash
# Application Settings
APP_NAME=USB CRM
APP_ENV=codecanyon
APP_DEBUG=false
APP_URL=http://your-domain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# HTTPS Redirect (set to true if using HTTPS)
REDIRECT_HTTPS=false
```

**Important Notes:**
- Do not change `APP_ENV` from `codecanyon` as it affects emailing and other configurations
- Replace `your_database_name`, `your_database_user`, and `your_database_password` with your actual database credentials
- Update `APP_URL` with your actual domain name

### Step 4: Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Create a database user (if needed):
```sql
CREATE USER 'your_database_user'@'localhost' IDENTIFIED BY 'your_database_password';
GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_database_user'@'localhost';
FLUSH PRIVILEGES;
```

3. Run database migrations:
```bash
php artisan migrate
```

4. Seed the database (optional, for initial data):
```bash
php artisan db:seed
```

### Step 5: Set Directory Permissions

Set proper permissions for Laravel directories:

```bash
# Set storage and cache directories permissions
chmod -R 775 storage bootstrap/cache

# Set ownership (replace www-data with your web server user)
chown -R www-data:www-data storage bootstrap/cache

# For Linux/Mac
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Step 6: Web Server Configuration

#### Apache Configuration

1. Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

2. Point your Apache virtual host to the `public` directory:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/e-learning-crm/public
    
    <Directory /var/www/html/e-learning-crm/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/e-learning-crm/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Step 7: Run the Installer

The application includes a web-based installer. Access it through your browser:

1. Navigate to: `http://your-domain.com/install`
2. Follow the installation wizard which will:
   - Check server requirements
   - Verify file permissions
   - Configure database connection
   - Complete the installation

### Step 8: Clear Cache and Optimize

After installation, optimize the application:

```bash
# Clear application cache
php artisan cache:clear

# Clear configuration cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Configuration

### Queue Configuration (Optional)

If you plan to use queues, configure your queue driver in `.env`:

```env
QUEUE_CONNECTION=database
```

Then create the queue table:
```bash
php artisan queue:table
php artisan migrate
```

### Queue Worker (Recommended for Performance)

For production-like performance, run the queue worker continuously instead of processing jobs synchronously:

```bash
php artisan queue:work database --queue=high,default,low --sleep=3 --tries=3 --max-time=3600
```

This project already schedules queue processing and failed-job pruning in `app/Console/Kernel.php`. Keep cron active:

```bash
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```

### Cron Job Setup

Set up a cron job to run scheduled tasks:

```bash
# Edit crontab
crontab -e

# Add this line (update the path to your project)
* * * * * cd /var/www/html/e-learning-crm && php artisan schedule:run >> /dev/null 2>&1
```

### Mail Configuration

Configure mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## Post-Installation

### Verify Installation

1. Check server requirements by visiting: `http://your-domain.com/check.php`
2. Access the application: `http://your-domain.com`
3. Log in with the default admin credentials (if provided)

### Security Checklist

- [ ] Change default admin password
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use HTTPS in production (`REDIRECT_HTTPS=true`)
- [ ] Restrict file permissions
- [ ] Keep dependencies updated
- [ ] Regularly backup your database

### Troubleshooting

**Issue: 500 Internal Server Error**
- Check file permissions on `storage` and `bootstrap/cache`
- Verify `.env` file exists and is configured correctly
- Check web server error logs

**Issue: Database Connection Error**
- Verify database credentials in `.env`
- Ensure database server is running
- Check database user has proper permissions

**Issue: mod_rewrite not working**
- Enable mod_rewrite: `sudo a2enmod rewrite`
- Restart Apache: `sudo systemctl restart apache2`
- Verify `.htaccess` file exists in `public` directory

**Issue: Permission Denied**
- Set correct ownership: `chown -R www-data:www-data storage bootstrap/cache`
- Set correct permissions: `chmod -R 775 storage bootstrap/cache`

## Performance Monitoring

- Request-level profiling is enabled via `TrackRequestPerformance` middleware.
- Slow queries are logged to `storage/logs/slow-query.log`.
- Request latency snapshots are stored in `request_performance_logs`.

### Useful Commands

- Run migrations (includes performance tables):
  - `php artisan migrate --force`
- View top slow endpoints and p95:
  - `php artisan perf:report --hours=24 --top=10`
- Prune old performance rows:
  - `php artisan perf:report --prune`

## Plugins Used

<ol>
    <li>
        <strong>Bootstrap 4 </strong> - <a href="https://getbootstrap.com/">https://getbootstrap.com/</a>
    </li>
    <li>
        <strong>Moment.js </strong> - <a href="https://momentjs.com/">https://momentjs.com/</a>
    </li>
    <li>
        <strong>Bootstrap Select</strong> - <a href="https://developer.snapappointments.com/bootstrap-select/">https://developer.snapappointments.com/bootstrap-select/</a>
    </li>
    <li>
        <strong>Datepicker </strong> - <a href="https://github.com/qodesmith/datepicker">https://github.com/qodesmith/datepicker</a>
    </li>
    <li>
        <strong>Fontawesome </strong> - <a href="https://fontawesome.com/">https://fontawesome.com/</a>
    </li>
    <li>
        <strong>Bootstrap Icons (used in menu) </strong> - <a href="https://icons.getbootstrap.com/">https://icons.getbootstrap.com/</a>
    </li>
    <li>
        <strong>Dropify (used for file uploads) </strong> - <a href="https://github.com/JeremyFagis/dropify">https://github.com/JeremyFagis/dropify</a>
    </li>
    <li>
        <strong>sweetalert2 (used for alerts and notifications)</strong> - <a href="https://sweetalert2.github.io/">https://sweetalert2.github.io/</a>
    </li>
    <li>
        <strong>Quilljs (used for rich text editor)</strong> - <a href="https://quilljs.com/">https://quilljs.com/</a>
    </li>
    <li>
        <strong>Frappe Charts</strong> - <a href="https://frappe.io/charts">https://frappe.io/charts</a>
    </li>
    <li>
        <strong>Bootstrap MultiDatesPicker</strong> - <a href="https://github.com/uxsolutions/bootstrap-datepicker">https://github.com/uxsolutions/bootstrap-datepicker</a>
    </li>
    <li>
        <strong>Bootstrap Colorpicker</strong> - <a href="https://github.com/itsjavi/bootstrap-colorpicker">https://github.com/itsjavi/bootstrap-colorpicker</a>
    </li>
    <li>
        <strong>jQuery UI (used for sortable items)</strong> - <a href="https://jqueryui.com/">https://jqueryui.com/</a>
    </li>
    <li>
        <strong>Highlight JS (used for highlight html content)</strong> - <a href="https://github.com/highlightjs/highlight.js">highlight.min.js</a>
    </li>
    <li>
        <strong>Chart.js</strong> - <a href="https://www.chartjs.org/">https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js</a>
    </li>
    <li>
        <strong>Image Picker</strong> - <a href="https://rvera.github.io/image-picker/">https://rvera.github.io/image-picker/</a>
    </li>
    <li>
        <strong>Cropper.js</strong> - <a href="https://github.com/fengyuanchen/cropperjs">https://github.com/fengyuanchen/cropperjs</a>
    </li>
</ol>

## Support

For additional support and documentation, please refer to the official documentation or contact support.

---

**Note**: This is a Laravel 10 application. Make sure you have the required PHP version (8.1.0+) and all necessary extensions installed before proceeding with the installation.
