#!/usr/bin/env bash

sleep 0.75 && exec /usr/bin/php /var/www/html/artisan reverb:start --port=9060 --host=0.0.0.0
