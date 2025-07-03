#!/bin/bash

# Alternative New Relic PHP Agent Installation Script
# This script can be used as a fallback if the Dockerfile method fails

set -e

PHP_VERSION=${1:-8.3}
NEW_RELIC_LICENSE_KEY=${2:-$NEW_RELIC_LICENSE_KEY}
NEW_RELIC_APP_NAME=${3:-$NEW_RELIC_APP_NAME}

if [ -z "$NEW_RELIC_LICENSE_KEY" ]; then
    echo "ERROR: NEW_RELIC_LICENSE_KEY is required"
    echo "Usage: $0 [php_version] [license_key] [app_name]"
    echo "   or: NEW_RELIC_LICENSE_KEY=your_key $0"
    exit 1
fi

echo "Installing New Relic PHP Agent..."
echo "PHP Version: $PHP_VERSION"
echo "App Name: $NEW_RELIC_APP_NAME"

# Create necessary directories
mkdir -p /var/log/newrelic
chmod 777 /var/log/newrelic
mkdir -p /etc/php/${PHP_VERSION}/fpm/conf.d/

# Download and install New Relic
ARCH=$(uname -m)
echo "Architecture: $ARCH"

if [ "$ARCH" = "x86_64" ]; then
    echo "Installing for x86_64..."
    # Use the official installation method
    curl -Ls https://download.newrelic.com/php_agent/scripts/newrelic.gpg | gpg --dearmor -o /etc/apt/trusted.gpg.d/newrelic.gpg
    echo "deb [signed-by=/etc/apt/trusted.gpg.d/newrelic.gpg] https://apt.newrelic.com/debian/ newrelic non-free" > /etc/apt/sources.list.d/newrelic.list
    
    apt-get update
    apt-get install -y newrelic-php5
    
    # Run the installer
    NR_INSTALL_KEY="$NEW_RELIC_LICENSE_KEY" NR_INSTALL_SILENT=1 newrelic-install install
    
elif [ "$ARCH" = "aarch64" ]; then
    echo "Installing for ARM64..."
    # Download ARM version directly
    curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-11.10.0.24-linux-musl.tar.gz -o newrelic.tar.gz
    tar -xzf newrelic.tar.gz
    cd newrelic-php5-*
    NR_INSTALL_USE_CP_NOT_LN=1 NR_INSTALL_SILENT=1 ./newrelic-install install
    cd ..
    rm -rf newrelic-php5-* newrelic.tar.gz
else
    echo "Unsupported architecture: $ARCH"
    echo "Creating minimal configuration..."
    cat > /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini << EOF
; New Relic PHP Agent configuration file
extension = newrelic.so
newrelic.enabled = true
EOF
fi

# Configure New Relic
if [ -f "/etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini" ]; then
    echo "Configuring New Relic..."
    cat >> /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini << EOF

; License and application settings
newrelic.license = "$NEW_RELIC_LICENSE_KEY"
newrelic.appname = "$NEW_RELIC_APP_NAME"

; Daemon settings for Docker
newrelic.daemon.address = newrelic-php-daemon:31339
newrelic.daemon.port = 31339
newrelic.daemon.docker = true

; Framework and monitoring settings
newrelic.framework = laravel
newrelic.browser_monitoring.auto_instrument = true
newrelic.transaction_tracer.enabled = true
newrelic.transaction_tracer.detail = 1
newrelic.loglevel = info
newrelic.logfile = /var/log/newrelic/php_agent.log
newrelic.daemon.logfile = /var/log/newrelic/newrelic-daemon.log
EOF

    echo "New Relic configuration written to /etc/php/${PHP_VERSION}/fmp/conf.d/newrelic.ini"
    echo "Configuration contents:"
    cat /etc/php/${PHP_VERSION}/fpm/conf.d/newrelic.ini
else
    echo "ERROR: New Relic configuration file not found!"
    exit 1
fi

echo "New Relic PHP Agent installation completed successfully!"
