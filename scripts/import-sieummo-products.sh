#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if docker compose ps --status running app >/dev/null 2>&1; then
  RUN=(docker compose exec -T app)
else
  RUN=(docker compose run --rm app)
fi

"${RUN[@]}" php artisan sieummo:import-products "$@"
