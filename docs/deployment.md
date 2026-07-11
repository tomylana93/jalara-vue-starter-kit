# Manual SSH Deployment

Jalara uses manual SSH deployment with atomic releases. GitHub Actions validates pull requests but does not connect to production.

## Server layout

Each deployed application lives below `/srv`:

```text
/srv/<app-name>/
├── current -> /srv/<app-name>/releases/<release-id>
├── releases/
│   ├── <release-id>
│   └── ...
└── shared/
    ├── .env
    └── storage/
```

The deployment script keeps the newest three releases by default.

## Runtime requirements

The production server must provide:

- PHP 8.5.x;
- Composer 2;
- Node.js 24.15.0;
- npm 11.17.0;
- pnpm 11.1.0;
- Git;
- access to the source repository through SSH.

## First-time setup

Replace `my-app` with the real application name:

```bash
sudo mkdir -p /srv/my-app/shared/storage/{app/public,framework/cache,framework/sessions,framework/views,logs}
sudo mkdir -p /srv/my-app/releases
sudo chown -R deploy:www-data /srv/my-app
sudo chmod -R g+rwX /srv/my-app
```

Create the production environment file:

```bash
sudo -u deploy nano /srv/my-app/shared/.env
```

The web server document root must point to:

```text
/srv/my-app/current/public
```

## Deploy

The script is intentionally invoked manually after a release has been merged and tagged:

```bash
ssh deploy@example.com
cd /path/to/deployment-tools

APP_NAME=my-app \
REPOSITORY=git@github.com:owner/application.git \
DEPLOY_REF=v1.2.3 \
bash scripts/deploy.sh
```

For a branch-based deployment:

```bash
APP_NAME=my-app \
REPOSITORY=git@github.com:owner/application.git \
DEPLOY_REF=main \
bash scripts/deploy.sh
```

Deploying immutable tags is preferred because the deployed source can be identified exactly.

## Custom paths and commands

The script accepts environment variables:

| Variable | Default | Purpose |
|---|---|---|
| `APP_NAME` | `jalara-app` | Directory name below `/srv` |
| `APP_ROOT` | `/srv/$APP_NAME` | Complete application root |
| `REPOSITORY` | Jalara starter-kit repository | Git clone source |
| `DEPLOY_REF` | `main` | Branch or tag to deploy |
| `KEEP_RELEASES` | `3` | Number of release directories retained |
| `PHP_BIN` | `php` | PHP executable |
| `COMPOSER_BIN` | `composer` | Composer executable |
| `PNPM_BIN` | `pnpm` | pnpm executable |

## Rollback

List the available releases:

```bash
ls -1dt /srv/my-app/releases/*
```

Point `current` to a previous release atomically:

```bash
ln -sfn /srv/my-app/releases/<previous-release> /srv/my-app/current.next
mv -Tf /srv/my-app/current.next /srv/my-app/current
cd /srv/my-app/current
php artisan optimize
php artisan queue:restart
```

Database migrations are not automatically rolled back. Migrations must therefore be backward-compatible with the immediately previous release whenever a fast application rollback is required.

## Long-running services

The script runs `queue:restart`. Configure Supervisor or systemd workers to use:

```text
/srv/my-app/current/artisan
```

The script also attempts to reload `php8.5-fpm` when systemd is available. If the deploy user cannot reload services, configure a narrowly scoped sudo rule or reload PHP-FPM manually after deployment.
