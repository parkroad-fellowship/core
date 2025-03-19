# syntax = docker/dockerfile:experimental

ARG PHP_VERSION=8.3
ARG NODE_VERSION=21
ARG NEW_RELIC_LICENSE_KEY
ARG NEW_RELIC_APP_NAME
ARG NEW_RELIC_AGENT_VERSION
FROM ubuntu:22.04 as base
LABEL fly_launch_runtime="laravel"

ARG PHP_VERSION
ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    NEW_RELIC_LICENSE_KEY=${NEW_RELIC_LICENSE_KEY} \
    NEW_RELIC_APP_NAME=${NEW_RELIC_APP_NAME} \
    NEW_RELIC_MONITOR_MODE=true \
    COMPOSER_HOME=/composer \
    COMPOSER_MAX_PARALLEL_HTTP=24 \
    PHP_PM_MAX_CHILDREN=10 \
    PHP_PM_START_SERVERS=3 \
    PHP_MIN_SPARE_SERVERS=2 \
    PHP_MAX_SPARE_SERVERS=4 \
    PHP_DATE_TIMEZONE=UTC \
    PHP_DISPLAY_ERRORS=Off \
    PHP_ERROR_REPORTING=22527 \
    PHP_MEMORY_LIMIT=256M \
    PHP_MAX_EXECUTION_TIME=90 \
    PHP_POST_MAX_SIZE=100M \
    PHP_UPLOAD_MAX_FILE_SIZE=100M \
    PHP_ALLOW_URL_FOPEN=Off

# Prepare base container: 
# 1. Install PHP, Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY .fly/php/ondrej_ubuntu_php.gpg /etc/apt/trusted.gpg.d/ondrej_ubuntu_php.gpg
ADD .fly/php/packages/${PHP_VERSION}.txt /tmp/php-packages.txt

RUN apt-get update \
    && apt-get install -y --no-install-recommends gnupg2 ca-certificates git-core curl zip unzip \
    rsync vim-tiny htop sqlite3 nginx supervisor cron ffmpeg \
    && ln -sf /usr/bin/vim.tiny /etc/alternatives/vim \
    && ln -sf /etc/alternatives/vim /usr/bin/vim \
    && echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ondrej-ubuntu-php-focal.list \
    && apt-get update \
    && apt-get -y --no-install-recommends install $(cat /tmp/php-packages.txt)

# # Separate New Relic installation - creates config only on ARM, full install on x86_64
# RUN curl -s https://download.newrelic.com/548C16BF.gpg | gpg --dearmor > /etc/apt/trusted.gpg.d/newrelic.gpg \
#     && echo "deb [arch=amd64] http://apt.newrelic.com/debian/ newrelic non-free" > /etc/apt/sources.list.d/newrelic.list \
#     && apt-get update \
#     && mkdir -p /etc/php/${PHP_VERSION}/fpm/conf.d/ \
#     && if [ "$(uname -m)" = "x86_64" ]; then \
#        apt-get -y install newrelic-php5 && \
#        NR_INSTALL_KEY=${NEW_RELIC_LICENSE_KEY} NR_INSTALL_SILENT=1 newrelic-install install; \
#     else \
#        echo "# New Relic PHP Agent configuration file" > /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini && \
#        echo "extension = newrelic.so" >> /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini && \
#        echo "newrelic.enabled = true" >> /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini && \
#        echo "newrelic.license = \"${NEW_RELIC_LICENSE_KEY}\"" >> /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini && \
#        echo "newrelic.appname = \"${NEW_RELIC_APP_NAME}\"" >> /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini && \
#        echo "newrelic.loglevel = \"info\"" >> /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini; \
#     fi \
#     && sed -i "s/newrelic.appname = .*/newrelic.appname = \"${NEW_RELIC_APP_NAME}\"/" /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini \
#     && sed -i "s/newrelic.license = .*/newrelic.license = \"${NEW_RELIC_LICENSE_KEY}\"/" /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini

RUN curl -L https://download.newrelic.com/php_agent/archive/${NEW_RELIC_AGENT_VERSION}/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux.tar.gz | tar -C /tmp -zx \
    && export NR_INSTALL_USE_CP_NOT_LN=1 \
    && export NR_INSTALL_SILENT=1 \
    && /tmp/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux/newrelic-install install \
    && rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*

RUN sed -i \
    -e "s/newrelic.license[[:space:]]*=[[:space:]]*.*/newrelic.license = ${NEW_RELIC_LICENSE_KEY}/" \
    -e "s/newrelic.appname[[:space:]]*=[[:space:]]*.*/newrelic.appname = ${NEW_RELIC_APPNAME}/" \
    -e "\$a newrelic.daemon.address=newrelic-php-daemon:31339" \
    /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini

# Continue with remaining setup
RUN ln -sf /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm \
    && mkdir -p /var/www/html/public && echo "index" > /var/www/html/public/index.php \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# 2. Copy config files to proper locations
COPY .fly/nginx/ /etc/nginx/
COPY .fly/fpm/ /etc/php/${PHP_VERSION}/fpm/
COPY .fly/supervisor/ /etc/supervisor/
COPY .fly/entrypoint.sh /entrypoint
COPY .fly/start-nginx.sh /usr/local/bin/start-nginx
COPY .fly/start-reverb.sh /usr/local/bin/start-reverb
COPY .fly/start-queue.sh /usr/local/bin/start-queue
COPY .fly/start-scheduler.sh /usr/local/bin/start-scheduler
RUN chmod 754 /usr/local/bin/start-nginx
RUN chmod 754 /usr/local/bin/start-reverb
RUN chmod 754 /usr/local/bin/start-queue
RUN chmod 754 /usr/local/bin/start-scheduler

# 3. Copy application code, skipping files based on .dockerignore
COPY . /var/www/html

WORKDIR /var/www/html

# 4. Setup application dependencies 
RUN composer install --optimize-autoloader --no-dev \
    && mkdir -p storage/logs \
    && php artisan optimize:clear \
    && chown -R www-data:www-data /var/www/html \
    && echo "MAILTO=\"\"\n* * * * * www-data /usr/bin/php /var/www/html/artisan schedule:run" > /etc/cron.d/laravel \
    && sed -i='' '/->withMiddleware(function (Middleware \$middleware) {/a\
    \$middleware->trustProxies(at: "*");\
    ' bootstrap/app.php; \ 
    if [ -d .fly ]; then cp .fly/entrypoint.sh /entrypoint; chmod +x /entrypoint; fi;


# If we're using Filament v3 and above, run caching commands...
RUN  php artisan icons:cache && php artisan filament:cache-components

# Multi-stage build: Build static assets
# This allows us to not include Node within the final container
FROM node:${NODE_VERSION} as node_modules_go_brrr

RUN mkdir /app

RUN mkdir -p  /app
WORKDIR /app
COPY . .
COPY --from=base /var/www/html/vendor /app/vendor

# Use yarn or npm depending on what type of
# lock file we might find. Defaults to
# NPM if no lock file is found.
# Note: We run "production" for Mix and "build" for Vite
RUN if [ -f "vite.config.js" ]; then \
    ASSET_CMD="build"; \
    else \
    ASSET_CMD="production"; \
    fi; \
    if [ -f "yarn.lock" ]; then \
    yarn install --frozen-lockfile; \
    yarn $ASSET_CMD; \
    elif [ -f "pnpm-lock.yaml" ]; then \
    corepack enable && corepack prepare pnpm@latest-8 --activate; \
    pnpm install --frozen-lockfile; \
    pnpm run $ASSET_CMD; \
    elif [ -f "package-lock.json" ]; then \
    npm ci --no-audit; \
    npm run $ASSET_CMD; \
    else \
    npm install; \
    npm run $ASSET_CMD; \
    fi;

# From our base container created above, we
# create our final image, adding in static
# assets that we generated above
FROM base

# Packages like Laravel Nova may have added assets to the public directory
# or maybe some custom assets were added manually! Either way, we merge
# in the assets we generated above rather than overwrite them
COPY --from=node_modules_go_brrr /app/public /var/www/html/public-npm
RUN rsync -ar /var/www/html/public-npm/ /var/www/html/public/ \
    && rm -rf /var/www/html/public-npm \
    && chown -R www-data:www-data /var/www/html/public

# 5. Setup Entrypoint
EXPOSE 8050 9060

ENTRYPOINT ["/entrypoint"]
