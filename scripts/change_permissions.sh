#!/bin/bash

# Setup the various file and folder permissions for Laravel
chmod -R 777 /var/www/html/bootstrap/ /var/www/html/storage /var/www/html/vendor /var/app/current/storage
chown -R www-data:www-data /var/www/html
