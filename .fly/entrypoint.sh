#!/usr/bin/env sh

# Run user scripts, if they exist
for f in /var/www/html/.fly/scripts/*.sh; do
    # Bail out this loop if any script exits with non-zero status code
    bash "$f" -e
done
chown -R www-data:www-data /var/www/html


if [ -n "${GOOGLE_APPLICATION_CREDENTIALS}" ]; then
    echo "Creating firebase-auth.json from environment variable..."
    echo "${GOOGLE_APPLICATION_CREDENTIALS}" | base64 -d > /var/www/html/firebase-auth.json
    chown www-data:www-data /var/www/html/firebase-auth.json
    echo "Firebase credentials file created successfully"
else
    echo "WARNING: GOOGLE_APPLICATION_CREDENTIALS environment variable is not set"
fi

if [ $# -gt 0 ]; then
    # If we passed a command, run it as root
    exec "$@"
else
    exec supervisord -c /etc/supervisor/supervisord.conf
fi
