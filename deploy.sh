#!/bin/bash

# Navigate to your project directory
cd /var/www/lavels/MVGBuilder

# Pull the latest code from the main branch
git pull --rebase

# Install/update Composer dependencies
composer update 

# Run database migrations
# php artisan migrate --force

# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optionally, restart queue workers, cache, etc.
# php artisan queue:restart

# Set permissions
#chown -R www-data:www-data /path/to/your/project
#chmod -R 775 /path/to/your/project/storage
#chmod -R 775 /path/to/your/project/bootstrap/cache

echo "Deployment completed successfully."
