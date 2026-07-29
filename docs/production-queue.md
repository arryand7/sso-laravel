# Production queue for bulk photo imports

Bulk photo imports require a continuously running Laravel queue worker. Deployment must not run `RoleSeeder`; apply the documented data patch separately.

## Required configuration

Set these values in the production environment without committing the production `.env` file:

```dotenv
QUEUE_CONNECTION=database
QUEUE_RETRY_AFTER=2400
USER_PHOTO_IMPORT_QUEUE=user-photo-imports
```

`QUEUE_RETRY_AFTER` must always be greater than the longest Job/worker timeout. The photo import Job and recommended worker timeout are 1800 seconds; 2400 seconds leaves a 10-minute safety margin. The database queue requires the standard `jobs` and `failed_jobs` tables to have been migrated.

## Worker operation

Run the worker under the production process supervisor:

```bash
php artisan queue:work database --queue=user-photo-imports --sleep=3 --tries=3 --backoff=60 --timeout=1800 --max-time=3600
```

After each deployment, gracefully ask existing workers to finish their current Job and restart with the new code:

```bash
php artisan queue:restart
```

Inspect failed Jobs without exposing their payloads in shared logs:

```bash
php artisan queue:failed
```

Retry or remove a failed Job only after its cause has been reviewed. To stop processing, stop the worker through its process supervisor. For an interactive worker, send `SIGTERM` (normally `Ctrl+C`) and allow the current Job to shut down gracefully.

Apply the production permission patch after the application migration and before enabling the feature:

```bash
php artisan gate:apply-data-patch add-user-photo-import-permissions
```
