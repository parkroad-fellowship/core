#!/usr/bin/env bash

/usr/bin/php /var/www/html/artisan config:cache --no-ansi
/usr/bin/php /var/www/html/artisan route:cache --no-ansi
/usr/bin/php /var/www/html/artisan view:cache --no-ansi
/usr/bin/php /var/www/html/artisan optimize --no-ansi
