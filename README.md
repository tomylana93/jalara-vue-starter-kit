# Jalara Vue Starter Kit

A modern Laravel starter kit built with Laravel, Inertia, Vue, Pest, and pnpm.

Jalara provides a structured application foundation intended for projects that value modularity, flexibility, modern tooling, and clear organization.

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js
- pnpm
- Laravel Installer

## Install from GitHub

During early development, the starter kit can be installed directly from the public GitHub repository:

```bash
laravel new my-app \
  --using=https://github.com/tomylana93/jalara-vue-starter-kit \
  --pnpm \
  --no-boost
```

Do not combine `--using` with `--vue`. The `--vue` option selects Laravel's official Vue starter kit instead of Jalara.

## Install from Packagist

After this repository is registered on Packagist, install it using its Composer package name:

```bash
laravel new my-app \
  --using=tomylana93/jalara-vue-starter-kit \
  --pnpm \
  --no-boost
```

The equivalent Composer command is:

```bash
composer create-project tomylana93/jalara-vue-starter-kit my-app
```

## Development

```bash
composer run setup
composer run dev
```

## Quality Checks

```bash
composer test
pnpm run lint:check
pnpm run format:check
pnpm run types:check
```

## Brand Assets

Official Jalara brand assets and visual identity guidelines are maintained in the [`tomylana93/jalara`](https://github.com/tomylana93/jalara) repository.

## License

This starter kit is open-sourced software licensed under the MIT License.

The Jalara name, logo, icons, and other brand assets are governed separately by the brand asset license in the [`tomylana93/jalara`](https://github.com/tomylana93/jalara) repository.
