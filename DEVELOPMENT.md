# Development Guide

## Requirements

- Docker + Docker Compose
- No local PHP or Composer install needed

## Start the environment

```bash
docker compose up -d
```

Wait ~30 seconds on first boot for MySQL to initialize. WordPress will not start until the database is healthy (healthcheck is configured).

| Service   | URL                    |
|-----------|------------------------|
| WordPress | http://localhost:8080  |
| Mailpit   | http://localhost:8025  |
| Adminer   | http://localhost:8081  |

On first launch, complete the WordPress installer at http://localhost:8080.

## Activate the plugin

Plugins > Installed Plugins > **WP Membership Registration** > Activate.

No build step required. The plugin uses `spl_autoload_register` — no `vendor/autoload.php` at runtime.

## Apply PHP code changes

File changes are reflected immediately — the plugin directory is volume-mounted into the container. Just refresh the browser (or the Plugins page if activating for the first time).

## Code quality (PHPCS/WPCS)

Install dev dependencies once using the Composer Docker image (no local Composer needed):

```bash
docker run --rm -v "$(pwd):/app" -w /app composer:latest install
```

Then run the linter:

```bash
docker compose exec wordpress /var/www/html/wp-content/plugins/wp-membership-registration/vendor/bin/phpcs \
  --standard=.phpcs.xml src/ wp-membership-registration.php
```

## Test email

All outgoing WordPress mail is captured by Mailpit — nothing is delivered to real addresses.

**Inbox:** http://localhost:8025

Send a test email from **Settings > Membership Registration > Email Settings > Send Test Email**, then open the inbox above to verify it arrived. SMTP runs on port 1025 inside the Docker network (not accessible from the host directly).

## Stop the environment

```bash
docker compose down
```

Add `-v` to also remove database and WordPress volumes (full reset):

```bash
docker compose down -v
```
