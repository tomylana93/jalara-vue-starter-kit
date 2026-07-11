#!/usr/bin/env bash
set -Eeuo pipefail

php_version_id="$(php -r 'echo PHP_VERSION_ID;')"
node_major="$(node -p 'process.versions.node.split(".")[0]')"
pnpm_major="$(pnpm -v | cut -d. -f1)"

if (( php_version_id < 80500 )); then
    echo "Expected PHP 8.5 or newer, got $(php -r 'echo PHP_VERSION;')" >&2
    exit 1
fi

if [[ "$node_major" != "24" ]]; then
    echo "Expected Node.js 24.x, got $(node -v)" >&2
    exit 1
fi

if [[ "$pnpm_major" != "11" ]]; then
    echo "Expected pnpm 11.x, got $(pnpm -v)" >&2
    exit 1
fi

echo "Runtime versions are valid."
