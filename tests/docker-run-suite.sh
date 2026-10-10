#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
VERIFIER_IMAGE="debian@sha256:7c7b2c966bc9ee8cedfeef67e0e279108992c77681fa595db4a9d65c06ccc587"
CACHE_HOST="${TMPDIR:-/tmp}/ps_sharebuttons_phpunit_cache"
PHAR_IN_CONTAINER="/phpunit-cache/phpunit-4.8.36.phar"
mkdir -p "${CACHE_HOST}"

DOCKER=(docker)
if ! docker info >/dev/null 2>&1; then
  DOCKER=(sudo docker)
fi

chmod +x "${ROOT}/tests/verify-phpunit-phar.sh" "${ROOT}/tests/run-tests.sh" "${ROOT}/tests/proof-pinterest-slash.sh"

if command -v curl >/dev/null 2>&1 && command -v gpg >/dev/null 2>&1 && command -v sha256sum >/dev/null 2>&1; then
  "${ROOT}/tests/verify-phpunit-phar.sh" "${CACHE_HOST}" >/dev/null
else
  "${DOCKER[@]}" run --rm \
    -v "${ROOT}:/work" \
    -v "${CACHE_HOST}:/phpunit-cache" \
    -w /work \
    "${VERIFIER_IMAGE}" bash -lc \
    'apt-get update -qq && apt-get install -y -qq curl gnupg ca-certificates >/dev/null && ./tests/verify-phpunit-phar.sh /phpunit-cache >/dev/null'
fi

if [[ ! -f "${CACHE_HOST}/phpunit-4.8.36.phar" ]]; then
  echo "Verified PHPUnit PHAR missing under ${CACHE_HOST}" >&2
  exit 1
fi

"${DOCKER[@]}" run --rm \
  -v "${ROOT}:/work" \
  -v "${CACHE_HOST}:/phpunit-cache:ro" \
  -w /work "${IMAGE}" bash -lc \
  "chmod +x tests/run-tests.sh tests/proof-pinterest-slash.sh && ./tests/run-tests.sh ${PHAR_IN_CONTAINER}"

"${DOCKER[@]}" run --rm \
  -v "${ROOT}:/work" \
  -v "${CACHE_HOST}:/phpunit-cache:ro" \
  -w /work "${IMAGE}" bash -lc \
  "./tests/proof-pinterest-slash.sh ${PHAR_IN_CONTAINER}"
