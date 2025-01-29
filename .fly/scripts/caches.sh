#!/usr/bin/env bash

/usr/bin/php /var/www/html/artisan config:cache --no-ansi -q
/usr/bin/php /var/www/html/artisan route:cache --no-ansi -q
/usr/bin/php /var/www/html/artisan view:cache --no-ansi -q
/usr/bin/php /var/www/html/artisan migrate --force --no-ansi -q
/usr/bin/php /var/www/html/artisan db:seed --class=ProductionSeeder --force --no-ansi -q
