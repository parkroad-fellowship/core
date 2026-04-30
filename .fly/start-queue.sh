#!/usr/bin/env bash

# Queue worker with memory management
# Supervisor runs 6 workers (see queue.conf) - requires 2GB+ VM
# --memory=256: Restart worker before hitting limit (6 workers = 1.5GB for queue)
# --timeout=240: Kill jobs running longer than 4 minutes
# --queue=high,default: Process high priority jobs first
# --max-jobs=100: Restart after 100 jobs to clear memory leaks

exec /usr/bin/php /var/www/html/artisan queue:work \
    --memory=256 \
    --timeout=240 \
    --queue=high,default \
    --max-jobs=100 \
    --sleep=3 \
    --tries=5 \
    --backoff=30
