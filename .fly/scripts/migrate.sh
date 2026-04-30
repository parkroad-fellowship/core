#!/usr/bin/env bash

/usr/bin/php /var/www/html/artisan migrate --force --no-ansi
/usr/bin/php /var/www/html/artisan db:seed --class=RolesAndPermissionsSeeder --force --no-ansi
/usr/bin/php /var/www/html/artisan db:seed --class=LeadershipPermissionsSeeder --force --no-ansi
# /usr/bin/php /var/www/html/artisan db:seed --class=ProductionSeeder --force --no-ansi: --- IGNORE: Meant to run once ---