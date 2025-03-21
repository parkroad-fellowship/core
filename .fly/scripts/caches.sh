#!/usr/bin/env bash

/usr/bin/php /var/www/html/artisan config:cache --no-ansi
/usr/bin/php /var/www/html/artisan route:cache --no-ansi
/usr/bin/php /var/www/html/artisan view:cache --no-ansi
/usr/bin/php /var/www/html/artisan migrate --force --no-ansi
# /usr/bin/php /var/www/html/artisan db:seed --class=ProductionSeeder --force --no-ansi
