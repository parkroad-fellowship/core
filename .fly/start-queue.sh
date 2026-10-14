#!/usr/bin/env bash

# Queue worker with memory management
# Supervisor runs 2 workers (see supervisor/conf.d/queue.conf)
# --memory=256:   restart the worker before it hits the memory limit
# --timeout=620:  longer than any job's #[Timeout] (max 600s) and shorter than retry_after (660s),
#                 so a slow job is never handed to a second worker while it is still running
# --queue:        high (payments, notifications) > default > long (AI, media, exports)
# --max-jobs=100: restart after 100 jobs to clear memory leaks
# Jobs declare their own #[Tries]/#[Backoff]; these flags are only the fallback.

exec /usr/bin/php /var/www/html/artisan queue:work \
    --memory=256 \
    --timeout=620 \
    --queue=high,default,long \
    --max-jobs=100 \
    --sleep=3 \
    --tries=3 \
    --backoff=30
