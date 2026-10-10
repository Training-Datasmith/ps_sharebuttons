#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHAR="${1:?phpunit phar path}"
PHP_BIN="${PHP_BIN:-php}"

PHP_FLAGS=(-d error_reporting=-1 -d display_errors=stderr -d display_startup_errors=1 -d log_errors=0)

if [[ "$("${PHP_BIN}" "${PHP_FLAGS[@]}" -r 'echo PHP_VERSION;')" != "5.6.40" ]]; then
  echo "PHP 5.6.40 required" >&2
  exit 1
fi

TMP_BASE="$(mktemp -d)"
trap 'rm -rf "${TMP_BASE}"' EXIT

JUNIT_SORTED="${TMP_BASE}/junit-sorted.xml"
JUNIT_SHUFFLE="${TMP_BASE}/junit-shuffle.xml"
SHUFFLE_XML="${TMP_BASE}/phpunit-shuffle.xml"

run_phpunit() {
  local config="$1"
  local junit="$2"
  local out="${TMP_BASE}/phpunit-out.txt"
  set +e
  "${PHP_BIN}" "${PHP_FLAGS[@]}" "${PHAR}" -c "${config}" --log-junit "${junit}" >"${out}" 2>&1
  local code=$?
  set -e
  if grep -E 'Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:|Catchable fatal error:|Recoverable fatal error:' "${out}"; then
    echo "PHPUnit run emitted PHP diagnostics" >&2
    cat "${out}" >&2
    exit 1
  fi
  if [[ ${code} -ne 0 ]]; then
    cat "${out}" >&2
    exit ${code}
  fi
  if grep -Ei 'skipped|incomplete|risky|warnings' "${out}"; then
    echo "PHPUnit run reported skipped/risky/incomplete/warnings" >&2
    cat "${out}" >&2
    exit 1
  fi
  if ! grep -q '^OK (' "${out}"; then
    cat "${out}" >&2
    exit 1
  fi
  echo "${out}"
}

OUT_SORTED="$(run_phpunit "${ROOT}/tests/phpunit.xml" "${JUNIT_SORTED}")"
OK_LINE_SORTED="$(grep '^OK (' "${OUT_SORTED}" | tail -n1)"

if [[ "${OK_LINE_SORTED}" != "OK (53 tests,"* ]]; then
  echo "Unexpected sorted test summary: ${OK_LINE_SORTED}" >&2
  exit 1
fi

export ROOT SHUFFLE_XML
GEN_OUT="${TMP_BASE}/shuffle-gen-out.txt"
"${PHP_BIN}" "${PHP_FLAGS[@]}" -r '
$root = getenv("ROOT");
$files = array(
  "ConfigFormTest.php",
  "DirectoryGuardTest.php",
  "InstallTest.php",
  "ModuleFileGuardTest.php",
  "ModuleMetadataTest.php",
  "RenderWidgetTest.php",
  "SuiteManifestTest.php",
  "UpgradeTest.php",
  "WidgetVariablesTest.php",
);
mt_srand(20261010);
for ($i = count($files) - 1; $i > 0; $i--) {
  $j = mt_rand(0, $i);
  $tmp = $files[$i];
  $files[$i] = $files[$j];
  $files[$j] = $tmp;
}
$out = getenv("SHUFFLE_XML");
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<phpunit bootstrap=\"" . $root . "/tests/bootstrap.php\" colors=\"false\" verbose=\"true\" beStrictAboutTestsThatDoNotTestAnything=\"true\" checkForUnintentionallyCoveredCode=\"true\" beStrictAboutOutputDuringTests=\"true\" beStrictAboutTestSize=\"true\" beStrictAboutTodoAnnotatedTests=\"true\" convertErrorsToExceptions=\"true\" convertNoticesToExceptions=\"true\" convertWarningsToExceptions=\"true\" backupGlobals=\"false\" backupStaticAttributes=\"false\">\n";
$xml .= "  <testsuites><testsuite name=\"module-tests\">\n";
foreach ($files as $f) {
  $xml .= "    <file>" . $root . "/tests/" . $f . "</file>\n";
}
$xml .= "  </testsuite></testsuites>\n</phpunit>\n";
file_put_contents($out, $xml);
' >"${GEN_OUT}" 2>&1
if grep -E 'Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:|Catchable fatal error:|Recoverable fatal error:' "${GEN_OUT}"; then
  echo "Shuffle generator emitted PHP diagnostics" >&2
  cat "${GEN_OUT}" >&2
  exit 1
fi
if [[ -s "${GEN_OUT}" ]]; then
  echo "Shuffle generator must not print output" >&2
  cat "${GEN_OUT}" >&2
  exit 1
fi

OUT_SHUFFLE="$(run_phpunit "${SHUFFLE_XML}" "${JUNIT_SHUFFLE}")"
OK_LINE_SHUFFLE="$(grep '^OK (' "${OUT_SHUFFLE}" | tail -n1)"

if [[ "${OK_LINE_SORTED}" != "${OK_LINE_SHUFFLE}" ]]; then
  echo "Sorted vs shuffled assertion counts differ" >&2
  echo "sorted: ${OK_LINE_SORTED}" >&2
  echo "shuffle: ${OK_LINE_SHUFFLE}" >&2
  exit 1
fi

echo "${OK_LINE_SORTED}"
cat "${OUT_SORTED}"
cat "${OUT_SHUFFLE}"
