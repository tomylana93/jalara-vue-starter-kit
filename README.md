# Jalara Vue Starter Kit

A modern Laravel starter kit built with Laravel, Inertia, Vue, Pest, and pnpm.

Jalara provides a structured application foundation intended for projects that value modularity, flexibility, modern tooling, and clear organization.

## Runtime

The supported toolchain is intentionally pinned:

- PHP 8.5.x
- Node.js 24.15.0
- npm 11.17.0
- pnpm 11.1.0
- Composer 2

Version files are included for Node, npm, and pnpm. Verify the local environment with:

```bash
bash scripts/check-runtime.sh
```

## Install from GitHub

During early development, install directly from the public repository:

```bash
laravel new my-app \
  --using=https://github.com/tomylana93/jalara-vue-starter-kit \
  --pnpm \
  --no-boost
```

Do not combine `--using` with `--vue`. The `--vue` option selects Laravel's official Vue starter kit instead of Jalara.

## Install from Packagist

After the repository is registered on Packagist:

```bash
laravel new my-app \
  --using=tomylana93/jalara-vue-starter-kit \
  --pnpm \
  --no-boost
```

Equivalent Composer command:

```bash
composer create-project tomylana93/jalara-vue-starter-kit my-app
```

## Development

```bash
composer run setup
composer run dev
```

## Quality checks

```bash
composer test
pnpm run lint:check
pnpm run format:check
pnpm run types:check
pnpm run build
```

GitHub Actions runs one required quality job for pull requests into `dev` and `main`. A separate fresh-install smoke test runs only for promotion pull requests into `main`, limiting runner time while still validating the starter-kit installation path.

## Branch and release flow

All changes to permanent branches must use pull requests:

```text
feature/* -> dev -> main -> version tag -> manual SSH deployment
```

See [`docs/development-workflow.md`](docs/development-workflow.md) for branch rules, merge strategy, hotfix handling, releases, and recommended GitHub rulesets.

## Manual deployment

Production deployment is manual over SSH. The included script creates atomic releases with this layout:

```text
/srv/<app-name>/
├── current
├── releases/
└── shared/
```

It keeps three releases by default and shares `.env` plus `storage` between releases. See [`docs/deployment.md`](docs/deployment.md).

## Brand assets

Official Jalara brand assets and visual identity guidelines are maintained in the [`tomylana93/jalara`](https://github.com/tomylana93/jalara) repository.

## License

This starter kit is open-sourced software licensed under the MIT License.

The Jalara name, logo, icons, and other brand assets are governed separately by the brand asset license in the [`tomylana93/jalara`](https://github.com/tomylana93/jalara) repository.
