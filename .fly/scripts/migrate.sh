#!/usr/bin/env bash

/usr/bin/php /var/www/html/artisan migrate --force --no-ansi
/usr/bin/php /var/www/html/artisan db:seed --class=RolesAndPermissionsSeeder --force --no-ansi
/usr/bin/php /var/www/html/artisan tenants:seed --class=RolesAndPermissionsSeeder --force --no-ansi
/usr/bin/php /var/www/html/artisan tenants:seed --class=LeadershipPermissionsSeeder --tenants=01kyvqgepfqh10z3r8wmeq6rcz --force --no-ansi
# /usr/bin/php /var/www/html/artisan db:seed --class=ProductionSeeder --force --no-ansi: --- IGNORE: Meant to run once ---