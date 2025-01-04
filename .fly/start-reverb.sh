#!/usr/bin/env bash

sleep 0.75 && exec /usr/bin/php /var/www/html/artisan reverb:start --no-ansi -q
