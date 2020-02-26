#!/bin/bash

# Clear any previous cached views and optimize the application
php /var/www/html/artisan cache:clear
php /var/www/html/artisan view:clear
php /var/www/html/artisan config:cache
php /var/www/html/artisan optimize
php /var/www/html/artisan route:cache

# Run composer
/usr/local/bin/composer install --no-ansi --no-suggest --no-interaction --no-progress --prefer-dist --no-scripts -d /var/www/html
#
# Run artisan commands
php /var/www/html/artisan migrate
