#!/usr/bin/env bash

# Ensure the site config is enabled
rm -f /etc/nginx/sites-enabled/default 2>/dev/null
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Test nginx configuration before starting
nginx -t

sleep 1.00 && exec nginx
