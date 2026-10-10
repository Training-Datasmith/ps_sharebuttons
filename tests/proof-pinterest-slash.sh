#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MODULE="${ROOT}/ps_sharebuttons.php"
PHAR="${1:?phpunit phar}"
BACKUP="$(mktemp)"
cp "${MODULE}" "${BACKUP}"
trap 'cp -f "${BACKUP}" "${MODULE}"; rm -f "${BACKUP}"' EXIT INT TERM
perl -pi -e 's/\$sharing_url \. '\''&media=/\$sharing_url . '\''\/&media=/ if /pinterest/' "${MODULE}"

set +e
OUT="$(php -d error_reporting=-1 -d display_errors=stderr "${PHAR}" -c "${ROOT}/tests/phpunit.xml" --filter testParsedShareTargetsRoundTripLinkNameAndImage 2>&1)"
CODE=$?
set -e

if echo "${OUT}" | grep -E 'Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:|Catchable fatal error:|Recoverable fatal error:'; then
  echo "Proof run emitted PHP diagnostics" >&2
  echo "${OUT}" >&2
  exit 1
fi

if [[ ${CODE} -eq 0 ]]; then
  echo "Proof failed: test passed with buggy Pinterest URL" >&2
  echo "${OUT}" >&2
  exit 1
fi

if ! echo "${OUT}" | grep -q 'FAILURES!'; then
  echo "Proof failed: unexpected PHPUnit outcome" >&2
  echo "${OUT}" >&2
  exit 1
fi

echo "Proof OK: Pinterest URL test fails with stray slash restored"
