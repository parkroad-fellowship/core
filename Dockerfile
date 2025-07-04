# syntax = docker/dockerfile:experimental

ARG PHP_VERSION=8.3
ARG NODE_VERSION=21
ARG NEW_RELIC_LICENSE_KEY
ARG NEW_RELIC_APP_NAME
ARG NEW_RELIC_AGENT_VERSION=11.6.0.19

FROM ubuntu:24.04 as base
LABEL fly_launch_runtime="laravel"

# Add these ARGs after FROM to make them available in this build stage
ARG NEW_RELIC_LICENSE_KEY
ARG NEW_RELIC_APP_NAME
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
    rsync vim-tiny htop sqlite3 nginx supervisor cron ffmpeg postgresql-client \
    && ln -sf /usr/bin/vim.tiny /etc/alternatives/vim \
    && ln -sf /etc/alternatives/vim /usr/bin/vim \
    && echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ondrej-ubuntu-php-noble.list \
    && apt-get update \
    && apt-get -y --no-install-recommends install $(cat /tmp/php-packages.txt)

# Separate New Relic installation - creates config only on ARM, full install on x86_64
COPY .fly/fpm/ /etc/php/${PHP_VERSION}/fpm/

# Install New Relic PHP Agent using script method (more reliable than APT)
COPY .fly/install-newrelic.sh /tmp/install-newrelic.sh
RUN chmod +x /tmp/install-newrelic.sh \
    && /tmp/install-newrelic.sh ${PHP_VERSION} "${NEW_RELIC_LICENSE_KEY}" "${NEW_RELIC_APP_NAME}" \
    && rm -f /tmp/install-newrelic.sh

# Install Chrome dependencies and configure for headless operation
RUN apt-get update && apt-get install -y --no-install-recommends \
    libx11-xcb1 libxcomposite1 libatk1.0-0 libatk-bridge2.0-0 libcairo2 libcups2 \
    libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 \
    libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcursor1 libxdamage1 \
    libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 \
    libnss3 libcups2 libdrm2 libxkbcommon0 \
    fonts-liberation fonts-noto-color-emoji fonts-noto-cjk \
    xdg-utils wget

# Install NodeJs
ARG NODE_VERSION
RUN curl -sL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs

# Install Chrome directly
RUN wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list \
    && apt-get update \
    && apt-get install -y google-chrome-stable

# Create necessary directories with proper permissions
RUN mkdir -p /tmp/.local/share/applications \
    && mkdir -p /tmp/.config \
    && mkdir -p /tmp/.cache \
    && mkdir -p /tmp/chrome-user-data \
    && touch /tmp/.local/share/applications/mimeapps.list \
    && chmod -R 777 /tmp/.local \
    && chmod -R 777 /tmp/.config \
    && chmod -R 777 /tmp/.cache \
    && chmod -R 777 /tmp/chrome-user-data

# Set environment variables for Chrome and Puppeteer
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH="/usr/bin/google-chrome-stable" \
    BROWSERSHOT_NODE_BINARY="/usr/bin/node" \
    BROWSERSHOT_CHROMIUM_PATH="/usr/bin/google-chrome-stable" \
    CHROME_PATH="/usr/bin/google-chrome-stable" \
    NODE_PATH="/usr/lib/node_modules" \
    HOME="/tmp" \
    XDG_CONFIG_HOME="/tmp/.config" \
    XDG_DATA_HOME="/tmp/.local/share" \
    XDG_CACHE_HOME="/tmp/.cache"

# Install puppeteer
RUN npm install -g puppeteer

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
COPY .fly/newrelic-troubleshoot.sh /usr/local/bin/newrelic-troubleshoot.sh
RUN chmod 754 /usr/local/bin/start-nginx
RUN chmod 754 /usr/local/bin/start-reverb
RUN chmod 754 /usr/local/bin/start-queue
RUN chmod 754 /usr/local/bin/start-scheduler
RUN chmod 754 /usr/local/bin/start-pulse
RUN chmod 754 /usr/local/bin/newrelic-troubleshoot.sh

# 3. Copy application code, skipping files based on .dockerignore
COPY . /var/www/html

WORKDIR /var/www/html

# 4. Setup application dependencies 
RUN composer install --optimize-autoloader --no-dev \
    && mkdir -p storage/logs \
    && mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && php artisan optimize:clear \
    && chown -R www-data:www-data /var/www/html \
    && echo "MAILTO=\"\"\n* * * * * www-data /usr/bin/php /var/www/html/artisan schedule:run" > /etc/cron.d/laravel \
    && sed -i 's|//|$middleware->trustProxies(at: "*");|' bootstrap/app.php \
    && if [ -d .fly ]; then cp .fly/entrypoint.sh /entrypoint; chmod +x /entrypoint; fi;


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

# Install Bun and build assets
RUN curl -fsSL https://bun.sh/install | bash && \
    export PATH="/root/.bun/bin:$PATH" && \
    if [ -f "vite.config.js" ]; then \
        bun install && bun run build; \
    else \
        bun install && bun run production; \
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
