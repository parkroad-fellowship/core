# syntax = docker/dockerfile:experimental

ARG PHP_VERSION=8.5
ARG NODE_VERSION=22

FROM ubuntu:24.04 as base
LABEL fly_launch_runtime="laravel"

# Add these ARGs after FROM to make them available in this build stage
ARG PHP_VERSION

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
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

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update \
    && apt-get install -y --no-install-recommends gnupg2 ca-certificates git-core curl zip unzip \
    rsync vim-tiny htop sqlite3 nginx supervisor cron ffmpeg postgresql-client acl \
    && ln -sf /usr/bin/vim.tiny /etc/alternatives/vim \
    && ln -sf /etc/alternatives/vim /usr/bin/vim \
    && echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ondrej-ubuntu-php-noble.list \
    && apt-get update \
    && apt-get -y --no-install-recommends install $(cat /tmp/php-packages.txt)

COPY .fly/fpm/ /etc/php/${PHP_VERSION}/fpm/

# Continue with remaining setup
RUN ln -sf /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm \
    && mkdir -p /var/www/html/public && echo "index" > /var/www/html/public/index.php \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /var/tmp/* /usr/share/doc/*

# 2. Copy config files to proper locations
COPY .fly/nginx/ /etc/nginx/
COPY .fly/supervisor/ /etc/supervisor/
COPY .fly/entrypoint.sh /entrypoint
COPY .fly/start-nginx.sh /usr/local/bin/start-nginx
COPY .fly/start-reverb.sh /usr/local/bin/start-reverb
COPY .fly/start-queue.sh /usr/local/bin/start-queue
COPY .fly/start-scheduler.sh /usr/local/bin/start-scheduler
COPY .fly/start-pulse.sh /usr/local/bin/start-pulse
RUN chmod 754 /usr/local/bin/start-nginx \
                /usr/local/bin/start-reverb \
                /usr/local/bin/start-queue \
                /usr/local/bin/start-scheduler \
                /usr/local/bin/start-pulse

WORKDIR /var/www/html

# 3. Copy composer files first for dependency caching
COPY composer.json composer.lock ./

# 4. Install composer dependencies (cached when composer files unchanged)
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --optimize-autoloader --no-dev --no-scripts --no-autoloader

# 5. Copy application code, skipping files based on .dockerignore
COPY . /var/www/html

# 6. Complete composer setup and application configuration
RUN --mount=type=cache,target=/root/.composer/cache \
    composer dump-autoload --optimize \
    && mkdir -p storage/logs \
    && mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && php artisan optimize:clear \
    && php artisan icons:cache \
    && php artisan filament:cache-components \
    && chown -R www-data:www-data /var/www/html \
    && echo "MAILTO=\"\"\n* * * * * www-data /usr/bin/php /var/www/html/artisan schedule:run" > /etc/cron.d/laravel \
    && sed -i='' '/->withMiddleware(function (Middleware \$middleware) {/a\
    \$middleware->trustProxies(at: "*");\
    ' bootstrap/app.php; \
    if [ -d .fly ]; then cp .fly/entrypoint.sh /entrypoint; chmod +x /entrypoint; fi;

# Multi-stage build: Build static assets
# This allows us to not include Node within the final container
FROM node:${NODE_VERSION} as node_modules_go_brrr

WORKDIR /app

# Copy package files first for better layer caching
COPY package.json package-lock.json* bun.lockb* ./

# Install Bun
RUN curl -fsSL https://bun.sh/install | bash
ENV PATH="/root/.bun/bin:$PATH"

# Install dependencies with cache mount (separate layer for better caching)
RUN --mount=type=cache,target=/root/.bun/install/cache \
    --mount=type=cache,target=/app/node_modules/.cache \
    bun install --frozen-lockfile || bun install

# Now copy the rest of the application code
COPY . .
COPY --from=base /var/www/html/vendor /app/vendor

# Build assets with cache mount
RUN --mount=type=cache,target=/app/node_modules/.cache \
    if [ -f "vite.config.js" ]; then \
        bun run build; \
    else \
        bun run production; \
    fi

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
