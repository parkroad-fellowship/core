#!/usr/bin/env bash

sleep 0.95 && exec /usr/bin/php /var/www/html/artisan queue:work --sleep=3 --tries=3 --backoff=3
