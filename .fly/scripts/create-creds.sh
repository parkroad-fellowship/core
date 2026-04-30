#!/usr/bin/env bash

if [ -n "${GOOGLE_APPLICATION_CREDENTIALS}" ]; then
    echo "Creating firebase-auth.json from environment variable..."
    echo "${GOOGLE_APPLICATION_CREDENTIALS}" | base64 -d > /var/www/html/storage/app/firebase-auth.json
    chown www-data:www-data /var/www/html/storage/app/firebase-auth.json
    echo "Firebase credentials file created successfully"
else
    echo "WARNING: GOOGLE_APPLICATION_CREDENTIALS environment variable is not set"
fi