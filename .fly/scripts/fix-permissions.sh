#!/usr/bin/env bash

# Ensure core Laravel directories exist
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public

# Set permissions on the storage directory (ownership is handled by entrypoint)
chmod -R 775 /var/www/html/storage
