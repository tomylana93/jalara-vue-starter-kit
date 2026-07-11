#!/usr/bin/env bash
set -Eeuo pipefail

required_php="8.5"
required_node="v24.15.0"
required_npm="11.17.0"
required_pnpm="11.1.0"

php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
node_version="$(node -v)"
npm_version="$(npm -v)"
pnpm_version="$(pnpm -v)"

[[ "$php_version" == "$required_php" ]] || { echo "Expected PHP $required_php.x, got $php_version" >&2; exit 1; }
[[ "$node_version" == "$required_node" ]] || { echo "Expected Node $required_node, got $node_version" >&2; exit 1; }
[[ "$npm_version" == "$required_npm" ]] || { echo "Expected npm $required_npm, got $npm_version" >&2; exit 1; }
[[ "$pnpm_version" == "$required_pnpm" ]] || { echo "Expected pnpm $required_pnpm, got $pnpm_version" >&2; exit 1; }

echo "Runtime versions are valid."
