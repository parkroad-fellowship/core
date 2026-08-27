#!/usr/bin/env bash

/usr/bin/php /var/www/html/artisan optimize:clear --no-ansi
/usr/bin/php /var/www/html/artisan pulse:restart --no-ansi
