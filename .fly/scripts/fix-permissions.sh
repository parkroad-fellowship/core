#!/usr/bin/env bash

# Ensure all required directories exist
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/cache/laravel-excel
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public

# Set ownership and permissions
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;

# Set the setgid bit on directories to ensure new files inherit group ownership
find /var/www/html/storage -type d -exec chmod g+s {} \;

# Set default ACLs for the logs and cache directories
setfacl -dm u::rwx,g::rwx,o::r-x /var/www/html/storage/logs
setfacl -dm u::rwx,g::rwx,o::r-x /var/www/html/storage/framework/cache/data
setfacl -dm u::rwx,g::rwx,o::r-x /var/www/html/storage/framework/cache/laravel-excel