#!/usr/bin/env bash
set -Eeuo pipefail

APP_NAME="${APP_NAME:-jalara-app}"
APP_ROOT="${APP_ROOT:-/srv/${APP_NAME}}"
REPOSITORY="${REPOSITORY:-git@github.com:tomylana93/jalara-vue-starter-kit.git}"
DEPLOY_REF="${DEPLOY_REF:-main}"
KEEP_RELEASES="${KEEP_RELEASES:-3}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
PNPM_BIN="${PNPM_BIN:-pnpm}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-}"

RELEASES_DIR="${APP_ROOT}/releases"
SHARED_DIR="${APP_ROOT}/shared"
CURRENT_LINK="${APP_ROOT}/current"
RELEASE_ID="$(date -u +%Y%m%d%H%M%S)"
RELEASE_PATH="${RELEASES_DIR}/${RELEASE_ID}"

log() {
    printf '[deploy] %s\n' "$*"
}

fail() {
    printf '[deploy] ERROR: %s\n' "$*" >&2
    exit 1
}

command -v git >/dev/null || fail 'git is required'
command -v "$PHP_BIN" >/dev/null || fail "${PHP_BIN} is required"
command -v "$COMPOSER_BIN" >/dev/null || fail "${COMPOSER_BIN} is required"
command -v node >/dev/null || fail 'node is required'
command -v "$PNPM_BIN" >/dev/null || fail "${PNPM_BIN} is required"

php_version_id="$($PHP_BIN -r 'echo PHP_VERSION_ID;')"
node_major="$(node -p 'process.versions.node.split(".")[0]')"
pnpm_major="$($PNPM_BIN -v | cut -d. -f1)"

(( php_version_id >= 80500 )) || fail "PHP 8.5 or newer required, got $($PHP_BIN -r 'echo PHP_VERSION;')"
[[ "$node_major" == "24" ]] || fail "Node.js 24.x required, got $(node -v)"
[[ "$pnpm_major" == "11" ]] || fail "pnpm 11.x required, got $($PNPM_BIN -v)"

mkdir -p "$RELEASES_DIR" "$SHARED_DIR/storage/app/public" "$SHARED_DIR/storage/framework/cache" "$SHARED_DIR/storage/framework/sessions" "$SHARED_DIR/storage/framework/views" "$SHARED_DIR/storage/logs"

[[ -f "$SHARED_DIR/.env" ]] || fail "Missing shared environment file: ${SHARED_DIR}/.env"

log "Creating release ${RELEASE_ID}"
git clone --depth 1 --branch "$DEPLOY_REF" "$REPOSITORY" "$RELEASE_PATH"

ln -sfn "$SHARED_DIR/.env" "$RELEASE_PATH/.env"
rm -rf "$RELEASE_PATH/storage"
ln -sfn "$SHARED_DIR/storage" "$RELEASE_PATH/storage"

cd "$RELEASE_PATH"

log 'Installing PHP dependencies'
"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

log 'Installing and building frontend assets'
"$PNPM_BIN" install --frozen-lockfile
"$PNPM_BIN" run build

log 'Running database migrations'
"$PHP_BIN" artisan migrate --force --ansi

log 'Caching application state'
"$PHP_BIN" artisan optimize --ansi
"$PHP_BIN" artisan storage:link --force --ansi || true

log 'Switching current symlink atomically'
ln -sfn "$RELEASE_PATH" "${CURRENT_LINK}.next"
mv -Tf "${CURRENT_LINK}.next" "$CURRENT_LINK"

log 'Restarting long-running processes'
"$PHP_BIN" "$CURRENT_LINK/artisan" queue:restart --ansi || true

if [[ -n "$PHP_FPM_SERVICE" ]] && command -v systemctl >/dev/null; then
    systemctl reload "$PHP_FPM_SERVICE" || true
fi

log "Removing old releases, keeping ${KEEP_RELEASES}"
mapfile -t releases < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort -r)
for old_release in "${releases[@]:$KEEP_RELEASES}"; do
    rm -rf "${RELEASES_DIR}/${old_release}"
done

log "Deployment complete: ${RELEASE_PATH}"
