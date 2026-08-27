#!/usr/bin/env bash

/usr/bin/php /var/www/html/artisan migrate --force --no-ansi
/usr/bin/php /var/www/html/artisan db:seed --class=RolesAndPermissionsSeeder --force --no-ansi
/usr/bin/php /var/www/html/artisan tenants:seed --class=LeadershipPermissionsSeeder --tenants=01kz9r08gfgb4v54k85rh8snmj --force --no-ansi
/usr/bin/php /var/www/html/artisan tenants:seed --class=RolesAndPermissionsSeeder --force --no-ansi
# /usr/bin/php /var/www/html/artisan db:seed --class=ProductionSeeder --force --no-ansi: --- IGNORE: Meant to run once ---