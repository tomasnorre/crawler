#!/usr/bin/env bash

#
# Installs TYPO3 in classic mode (without Composer) with the crawler extension as it is packaged for the TER,
# and the content, site and extensions of the devbox (.devbox), for the acceptance tests (Tests/Acceptance).
# Checks that the crawler commands are available and that the frontend and the backend respond,
# then leaves the web server running for the acceptance tests.
#
# The extension is taken from the committed state (HEAD), packaged with `git archive`,
# so files marked with export-ignore in .gitattributes are left out, like in the TER package.
#
# Usage: Build/Scripts/classicModeSetup.sh [TYPO3 version] [working directory]
#   TYPO3 version      Version to download from get.typo3.org, fx. "14" for the latest 14.x or "14.3.0", default "14"
#   working directory  Where TYPO3 is installed, must not exist yet, default: a new temporary directory
#
# The database must be an empty MySQL/MariaDB database, configured by DB_HOST, DB_PORT, DB_NAME, DB_USER and
# DB_PASSWORD, default 127.0.0.1, 3306 and "db" for the rest.
#
# Needs PHP with mysqli and pdo_mysql, curl, tar and git. TYPO3 is served by the PHP built-in web server on port 8080.
# Run the acceptance tests against it with Build/Scripts/classicModeSql.php, see Tests/Acceptance/Tests/helpers.js.
#

set -euo pipefail

TYPO3_VERSION="${1:-14}"
WORK_DIR="${2:-$(mktemp -d)}"
EXTENSION_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HOST="127.0.0.1:8080"
BASE_URL="http://${HOST}/"

# Extensions of the devbox which are not part of TYPO3, the versions installed by .devbox/composer.json
DEVBOX_EXTENSIONS=(
    "news https://codeload.github.com/georgringer/news/tar.gz/36a5a983022f5648352975142d9daef6f77a657d"
    "crawler_devbox_sitepackage https://codeload.github.com/tomasnorre/crawler-devbox-sitepackage/tar.gz/718d7b794df01ce3f3ecd353ad914eca98f0dc7e"
)

# System extensions .devbox/composer.json installs, besides the ones every installation has
DEVBOX_SYSTEM_EXTENSIONS=(fluid_styled_content rte_ckeditor seo belog beuser felogin indexed_search info scheduler tstemplate)

step() {
    echo
    echo "### $*"
}

fail() {
    echo "::error::$*"
    exit 1
}

mkdir -p "${WORK_DIR}"
cd "${WORK_DIR}"
WORK_DIR="$(pwd)"
PUBLIC_DIR="${WORK_DIR}/public"
TYPO3_BIN="${PUBLIC_DIR}/typo3/sysext/core/bin/typo3"

typo3() {
    php "${TYPO3_BIN}" "$@"
}

# Runs PHP code with a PDO connection to the database of the installation as $pdo,
# further arguments are available from $argv[4]
sql() {
    php "${EXTENSION_DIR}/Build/Scripts/classicModeSql.php" "${PUBLIC_DIR}" --eval "$@"
}

# Requests a URL, follows redirects and fails with the response if the final status is not 200
check_url() {
    local description="$1" url="$2" status
    status="$(curl --silent --location --output "${WORK_DIR}/response.html" --write-out '%{http_code} %{url_effective}' "${url}")"
    echo "${description}: ${status}"
    if [ "${status%% *}" != "200" ]; then
        php -r '
            $html = preg_replace("#<(style|script)\\b.*?</\\1>#is", "", (string) file_get_contents($argv[1]));
            $text = html_entity_decode(strip_tags(preg_replace("#<(br|p|div|h\d|li|tr|pre|span)\b#i", "\n$0", $html)));
            echo substr(preg_replace("/\n\s*\n+/", "\n", $text), 0, 6000), "\n";
        ' "${WORK_DIR}/response.html"
        fail "${description} returned ${status}"
    fi
}

step "Download TYPO3 ${TYPO3_VERSION}"
curl --fail --silent --show-error --location "https://get.typo3.org/${TYPO3_VERSION}/tar.gz" --output typo3.tar.gz
tar -xzf typo3.tar.gz
SOURCE_DIR="$(find . -maxdepth 1 -type d -name 'typo3_src-*' | head -n 1)"
[ -n "${SOURCE_DIR}" ] || fail "No typo3_src-* directory found in the download"
echo "Using ${SOURCE_DIR#./}"

step "Create the classic mode document root"
mkdir -p "${PUBLIC_DIR}/typo3conf/ext"
ln -s "../${SOURCE_DIR#./}" "${PUBLIC_DIR}/typo3_src"
ln -s typo3_src/typo3 "${PUBLIC_DIR}/typo3"
ln -s typo3_src/index.php "${PUBLIC_DIR}/index.php"

step "Add the crawler extension as packaged for the TER"
git -C "${EXTENSION_DIR}" archive --format=tar --prefix=crawler/ HEAD | tar -x -C "${PUBLIC_DIR}/typo3conf/ext"
[ ! -e "${PUBLIC_DIR}/typo3conf/ext/crawler/Tests" ] || fail "Tests/ is part of the package, check .gitattributes"
[ -f "${PUBLIC_DIR}/typo3conf/ext/crawler/composer.json" ] || fail "composer.json is missing in the package"

step "Add the extensions of the devbox"
for extension in "${DEVBOX_EXTENSIONS[@]}"; do
    read -r key url <<< "${extension}"
    mkdir -p "${PUBLIC_DIR}/typo3conf/ext/${key}"
    curl --fail --silent --show-error --location "${url}" \
        | tar -xz --strip-components=1 -C "${PUBLIC_DIR}/typo3conf/ext/${key}"
    echo "Added ${key}"
done

step "Set up TYPO3"
TYPO3_SETUP_ADMIN_PASSWORD="$(php -r 'echo bin2hex(random_bytes(8));')Aa1!"
export TYPO3_SETUP_ADMIN_PASSWORD
export TYPO3_DB_DRIVER=mysqli
export TYPO3_DB_HOST="${DB_HOST:-127.0.0.1}"
export TYPO3_DB_PORT="${DB_PORT:-3306}"
export TYPO3_DB_DBNAME="${DB_NAME:-db}"
export TYPO3_DB_USERNAME="${DB_USER:-db}"
export TYPO3_DB_PASSWORD="${DB_PASSWORD:-db}"
typo3 setup \
    --no-interaction \
    --force \
    --admin-username=admin \
    --admin-email=admin@example.com \
    --project-name="Crawler classic mode" \
    --server-type=other

step "Activate the extensions"
for extension in "${DEVBOX_SYSTEM_EXTENSIONS[@]}" crawler news crawler_devbox_sitepackage; do
    typo3 extension:activate "${extension}"
done
typo3 extension:setup
EXTENSIONS="$(typo3 extension:list)"
grep -qw crawler <<< "${EXTENSIONS}" || fail "The crawler extension is not active"

step "Import the content and the site of the devbox"
gzip -dc "${EXTENSION_DIR}/.devbox/build-files/db-14.sql.gz" \
    | php "${EXTENSION_DIR}/Build/Scripts/classicModeSql.php" "${PUBLIC_DIR}" --import
# Adds what the extensions need beyond the dump
typo3 extension:setup
# The crawler configurations of the devbox crawl the devbox host
sql '
    $pdo->prepare("UPDATE tx_crawler_configuration SET base_url = ? WHERE base_url <> ?")
        ->execute([rtrim($argv[4], "/"), ""]);
    $pdo->exec("DELETE FROM tx_crawler_queue");
' "${BASE_URL}"
mkdir -p "${PUBLIC_DIR}/typo3conf/sites"
cp -R "${EXTENSION_DIR}/.devbox/config/sites/." "${PUBLIC_DIR}/typo3conf/sites/"
cp -R "${EXTENSION_DIR}/.devbox/public/fileadmin" "${PUBLIC_DIR}/"
typo3 cache:flush

step "Check the crawler commands"
# Call the commands directly as well, an error creating them is shown on stderr
typo3 crawler:buildQueue --help > /dev/null || fail "crawler:buildQueue cannot be run"
typo3 crawler:flushQueue --help > /dev/null || fail "crawler:flushQueue cannot be run"
COMMANDS="$(typo3 list --raw)"
grep -q '^crawler:buildQueue ' <<< "${COMMANDS}" || fail "crawler:buildQueue is not listed"
grep -q '^crawler:flushQueue ' <<< "${COMMANDS}" || fail "crawler:flushQueue is not listed"

step "Start the web server on ${BASE_URL}"
# The PHP built-in web server sets SCRIPT_NAME to the requested path for directories like /typo3/, and TYPO3
# derives its paths from it. The router serves existing files and passes everything else to index.php,
# with the server variables a web server like Apache or nginx sets.
cat > "${WORK_DIR}/router.php" <<'PHP'
<?php
$publicDir = __DIR__ . '/public';
$path = (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($publicDir . $path)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicDir . '/index.php';
chdir($publicDir);
require $publicDir . '/index.php';
PHP
# The Development context shows the details of exceptions in the response. Several workers let the backend
# load its frames and the crawler request pages while a request is running.
# The server keeps running after this script, for the acceptance tests.
(cd "${PUBLIC_DIR}" && TYPO3_CONTEXT=Development PHP_CLI_SERVER_WORKERS=4 \
    php -S "${HOST}" -t "${PUBLIC_DIR}" "${WORK_DIR}/router.php" > "${WORK_DIR}/php-server.log" 2>&1) &
SERVER_PID=$!
for _ in $(seq 1 30); do
    curl --silent --output /dev/null "${BASE_URL}" && break
    sleep 1
done

step "Check the frontend and the backend"
check_url "The frontend" "${BASE_URL}"
check_url "The backend login" "${BASE_URL}typo3/"

step "TYPO3 ${TYPO3_VERSION} in classic mode is running on ${BASE_URL} (PID ${SERVER_PID})"
