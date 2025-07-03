#!/bin/bash

# New Relic Troubleshooting Script
# This script helps diagnose common New Relic setup issues

echo "🔍 New Relic Troubleshooting Script"
echo "===================================="

# Check if environment variables are set
echo "📋 Checking environment variables..."
if [ -z "$NEW_RELIC_LICENSE_KEY" ]; then
    echo "❌ NEW_RELIC_LICENSE_KEY is not set"
    echo "   Export it: export NEW_RELIC_LICENSE_KEY=your_license_key"
else
    echo "✅ NEW_RELIC_LICENSE_KEY is set"
fi

if [ -z "$NEW_RELIC_APP_NAME" ]; then
    echo "❌ NEW_RELIC_APP_NAME is not set"
    echo "   Export it: export NEW_RELIC_APP_NAME='Your App Name'"
else
    echo "✅ NEW_RELIC_APP_NAME is set: $NEW_RELIC_APP_NAME"
fi

echo ""

# Check Docker containers
echo "🐳 Checking Docker containers..."
if docker ps | grep -q "newrelic-php-daemon"; then
    echo "✅ New Relic PHP Daemon is running"
    echo "   Container status:"
    docker ps --filter name=newrelic-php-daemon --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
else
    echo "❌ New Relic PHP Daemon is not running"
    echo "   Start it with: make daemon"
fi

if docker ps | grep -q "newrelic-infra"; then
    echo "✅ New Relic Infrastructure agent is running"
else
    echo "❌ New Relic Infrastructure agent is not running"
    echo "   Start it with: make infra"
fi

echo ""

# Check network connectivity
echo "🌐 Checking network connectivity..."
if docker network ls | grep -q "kamal"; then
    echo "✅ Kamal network exists"
else
    echo "❌ Kamal network does not exist"
    echo "   Create it with: docker network create kamal"
fi

echo ""

# Check PHP configuration (if running in container)
echo "🐘 Checking PHP New Relic configuration..."
if command -v php >/dev/null 2>&1; then
    if php -m | grep -q newrelic; then
        echo "✅ New Relic PHP extension is loaded"
        echo "   New Relic version: $(php -r "echo phpversion('newrelic');" 2>/dev/null || echo 'Unknown')"
    else
        echo "❌ New Relic PHP extension is not loaded"
    fi
    
    # Check for New Relic ini files
    if ls /etc/php/*/fpm/conf.d/newrelic.ini >/dev/null 2>&1; then
        echo "✅ New Relic INI file found:"
        ls /etc/php/*/fpm/conf.d/newrelic.ini
    else
        echo "❌ New Relic INI file not found"
    fi
else
    echo "ℹ️  PHP not available in current environment (normal for host system)"
fi

echo ""

# Show logs
echo "📝 Recent container logs:"
echo "--- New Relic PHP Daemon logs (last 10 lines) ---"
if docker ps | grep -q "newrelic-php-daemon"; then
    docker logs --tail 10 newrelic-php-daemon 2>/dev/null || echo "No logs available"
else
    echo "Container not running"
fi

echo ""
echo "--- New Relic Infrastructure logs (last 10 lines) ---"
if docker ps | grep -q "newrelic-infra"; then
    docker logs --tail 10 newrelic-infra 2>/dev/null || echo "No logs available"
else
    echo "Container not running"
fi

echo ""
echo "🔧 Quick fixes:"
echo "1. Set environment variables: export NEW_RELIC_LICENSE_KEY=your_key"
echo "2. Start containers: make newrelic-setup"
echo "3. Check logs: make newrelic-logs"
echo "4. Restart everything: make newrelic-restart"
echo "5. Rebuild Docker image: make build"
