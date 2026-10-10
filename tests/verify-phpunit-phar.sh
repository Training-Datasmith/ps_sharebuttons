#!/usr/bin/env bash
set -euo pipefail

CACHE_DIR="${1:?cache dir required}"
PHAR="${CACHE_DIR}/phpunit-4.8.36.phar"
ASC="${CACHE_DIR}/phpunit-4.8.36.phar.asc"
EXPECTED_SHA="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"
EXPECTED_FP="D8406D0D82947747293778314AA394086372C20A"

mkdir -p "${CACHE_DIR}"

if [[ ! -f "${PHAR}" ]]; then
  curl -fsSL "https://phar.phpunit.de/phpunit-4.8.36.phar" -o "${PHAR}"
  curl -fsSL "https://phar.phpunit.de/phpunit-4.8.36.phar.asc" -o "${ASC}"
fi

ACTUAL_SHA="$(sha256sum "${PHAR}" | awk '{print $1}')"
if [[ "${ACTUAL_SHA}" != "${EXPECTED_SHA}" ]]; then
  echo "PHPUnit PHAR SHA-256 mismatch: ${ACTUAL_SHA}" >&2
  exit 1
fi

export GNUPGHOME="$(mktemp -d)"
chmod 700 "${GNUPGHOME}"
gpg --batch --keyserver hkps://keys.openpgp.org --recv-keys 0x4AA394086372C20A
VERIFY_OUT="$(gpg --batch --status-fd 1 --verify "${ASC}" "${PHAR}" 2>&1)"
VERIFY_STATUS=$?
if [[ ${VERIFY_STATUS} -ne 0 ]]; then
  echo "PHPUnit PHAR GPG verify failed (exit ${VERIFY_STATUS})" >&2
  echo "${VERIFY_OUT}" >&2
  rm -rf "${GNUPGHOME}"
  exit 1
fi
echo "${VERIFY_OUT}" | grep -q "VALIDSIG"
FP_LINE="$(echo "${VERIFY_OUT}" | grep '^\[GNUPG:\] VALIDSIG' | head -n1)"
if [[ -z "${FP_LINE}" ]]; then
  echo "PHPUnit PHAR missing VALIDSIG" >&2
  rm -rf "${GNUPGHOME}"
  exit 1
fi
FP="$(echo "${FP_LINE}" | awk '{print $NF}')"
if [[ "${FP}" != "${EXPECTED_FP}" ]]; then
  echo "PHPUnit PHAR fingerprint mismatch: ${FP}" >&2
  rm -rf "${GNUPGHOME}"
  exit 1
fi
rm -rf "${GNUPGHOME}"

echo "${PHAR}"
