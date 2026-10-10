#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
CACHE_HOST="${TMPDIR:-/tmp}/ps_sharebuttons_phpunit_cache"
mkdir -p "${CACHE_HOST}"

DOCKER=(docker)
if ! docker info >/dev/null 2>&1; then
  DOCKER=(sudo docker)
fi

chmod +x "${ROOT}/tests/verify-phpunit-phar.sh" "${ROOT}/tests/run-tests.sh" "${ROOT}/tests/proof-pinterest-slash.sh"

APT_FIX="sed -i -e 's/deb.debian.org/archive.debian.org/g' -e 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' -e '/stretch-updates/d' /etc/apt/sources.list && echo 'Acquire::Check-Valid-Until \"false\";' > /etc/apt/apt.conf.d/99no-check-valid-until"
PHAR_PATH="$("${DOCKER[@]}" run --rm -v "${ROOT}:/work" -v "${CACHE_HOST}:/phpunit-cache" -w /work "${IMAGE}" bash -lc \
  "${APT_FIX} && apt-get update -qq && apt-get install -y -qq --allow-unauthenticated curl gnupg ca-certificates >/dev/null && ./tests/verify-phpunit-phar.sh /phpunit-cache")"

"${DOCKER[@]}" run --rm -v "${ROOT}:/work" -v "${CACHE_HOST}:/phpunit-cache" -w /work "${IMAGE}" bash -lc \
  "chmod +x tests/run-tests.sh tests/proof-pinterest-slash.sh && ./tests/run-tests.sh /phpunit-cache/phpunit-4.8.36.phar"

"${DOCKER[@]}" run --rm -v "${ROOT}:/work" -v "${CACHE_HOST}:/phpunit-cache" -w /work "${IMAGE}" bash -lc \
  "./tests/proof-pinterest-slash.sh /phpunit-cache/phpunit-4.8.36.phar"
