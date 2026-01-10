#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Error: not a git repository." >&2
  exit 1
fi

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "Error: working tree has uncommitted changes. Commit or stash tracked changes first." >&2
  exit 1
fi

composer_bin="${COMPOSER_BIN:-composer}"
php_bin="${PHP_BIN:-php}"
remote="origin"
branch="$(git rev-parse --abbrev-ref HEAD)"

git fetch --prune "$remote"

local_sha="$(git rev-parse "$branch")"
remote_sha="$(git rev-parse "$remote/$branch")"

if [ "$local_sha" = "$remote_sha" ]; then
  echo "Already up to date."
  exit 0
fi

echo "Updating $branch from $remote..."
git pull --ff-only "$remote" "$branch"

if [ -f composer.json ]; then
  echo "Installing Composer dependencies..."
  COMPOSER_ALLOW_SUPERUSER=1 "$php_bin" "$composer_bin" install --no-interaction --prefer-dist --optimize-autoloader --no-dev
fi

if [ -f artisan ]; then
  echo "Running database migrations..."
  "$php_bin" artisan migrate --force

  echo "Refreshing cached data..."
  "$php_bin" artisan optimize
fi

echo "Upgrade complete."
