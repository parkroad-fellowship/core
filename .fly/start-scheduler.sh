#!/usr/bin/env bash

sleep 0.99 && exec /usr/bin/php /var/www/html/artisan schedule:run
