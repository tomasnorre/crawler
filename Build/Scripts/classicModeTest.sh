#!/usr/bin/env bash

#
# Installs TYPO3 in classic mode (without Composer) with the crawler extension as it is packaged for the TER,
# and checks that it works.
#
# The extension is taken from the committed state (HEAD), packaged with `git archive`,
# so files marked with export-ignore in .gitattributes are left out, like in the TER package.
#
# Usage: Build/Scripts/classicModeTest.sh [TYPO3 version] [working directory] [setup]
#   TYPO3 version      Version to download from get.typo3.org, fx. "14" for the latest 14.x or "14.3.0", default "14"
#   working directory  Where TYPO3 is installed, must not exist yet, default: a new temporary directory
#   setup              "minimal" (default): an SQLite installation with a crawler configuration. Checks that the
#                      crawler commands are available, and that the crawler builds a queue and crawls it,
#                      directly and through the message bus. The web server is stopped at the end.
#                      "devbox": the content, site and extensions of the devbox (.devbox) on MySQL/MariaDB, for the
#                      acceptance tests (Tests/Acceptance). The web server keeps running for them.
#                      The database is configured by DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD,
#                      default 127.0.0.1, 3306 and "db" for the rest, it must be empty.
#
# Needs PHP with pdo_sqlite (minimal) or mysqli and pdo_mysql (devbox), curl, tar and git.
# TYPO3 is served by the PHP built-in web server on port 8080.
#

set -euo pipefail

TYPO3_VERSION="${1:-14}"
WORK_DIR="${2:-$(mktemp -d)}"
SETUP="${3:-minimal}"
EXTENSION_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HOST="127.0.0.1:8080"
BASE_URL="http://${HOST}/"

# Extensions of the devbox which are not part of TYPO3, the versions installed by .devbox/composer.json
DEVBOX_EXTENSIONS=(
    "news https://codeload.github.com/georgringer/news/tar.gz/36a5a983022f5648352975142d9daef6f77a657d"
    "crawler_devbox_sitepackage https://codeload.github.com/tomasnorre/crawler-devbox-sitepackage/tar.gz/718d7b794df01ce3f3ecd353ad914eca98f0dc7e"
)

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

case "${SETUP}" in
    minimal | devbox) ;;
    *) fail "Unknown setup \"${SETUP}\", use \"minimal\" or \"devbox\"" ;;
esac

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

if [ "${SETUP}" = "devbox" ]; then
    step "Add the extensions of the devbox"
    for extension in "${DEVBOX_EXTENSIONS[@]}"; do
        read -r key url <<< "${extension}"
        mkdir -p "${PUBLIC_DIR}/typo3conf/ext/${key}"
        curl --fail --silent --show-error --location "${url}" \
            | tar -xz --strip-components=1 -C "${PUBLIC_DIR}/typo3conf/ext/${key}"
        echo "Added ${key}"
    done
fi

step "Set up TYPO3"
TYPO3_SETUP_ADMIN_PASSWORD="$(php -r 'echo bin2hex(random_bytes(8));')Aa1!"
export TYPO3_SETUP_ADMIN_PASSWORD
if [ "${SETUP}" = "devbox" ]; then
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
else
    typo3 setup \
        --no-interaction \
        --force \
        --driver=sqlite \
        --admin-username=admin \
        --admin-email=admin@example.com \
        --project-name="Crawler classic mode" \
        --create-site="${BASE_URL}" \
        --server-type=other
fi

step "Activate the crawler extension"
typo3 extension:activate info
typo3 extension:activate crawler
if [ "${SETUP}" = "devbox" ]; then
    # The extensions .devbox/composer.json installs, besides the ones every installation has
    for extension in fluid_styled_content rte_ckeditor seo belog beuser felogin indexed_search scheduler tstemplate \
        news crawler_devbox_sitepackage; do
        typo3 extension:activate "${extension}"
    done
fi
typo3 extension:setup
typo3 cache:flush
EXTENSIONS="$(typo3 extension:list)"
grep -qw crawler <<< "${EXTENSIONS}" || fail "The crawler extension is not active"

if [ "${SETUP}" = "devbox" ]; then
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
fi

step "Check the crawler commands"
# Call the commands directly as well, an error creating them is shown on stderr
typo3 crawler:buildQueue --help > /dev/null || fail "crawler:buildQueue cannot be run"
typo3 crawler:flushQueue --help > /dev/null || fail "crawler:flushQueue cannot be run"
COMMANDS="$(typo3 list --raw)"
grep -q '^crawler:buildQueue ' <<< "${COMMANDS}" || fail "crawler:buildQueue is not listed"
grep -q '^crawler:flushQueue ' <<< "${COMMANDS}" || fail "crawler:flushQueue is not listed"

if [ "${SETUP}" = "minimal" ]; then
    step "Add a crawler configuration to the root page"
    sql '
        $pdo->prepare("INSERT INTO tx_crawler_configuration
            (pid, tstamp, crdate, name, configuration, base_url, pidsonly, exclude, begroups, fegroups,
             processing_instruction_filter, processing_instruction_parameters_ts, force_ssl)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([1, time(), time(), "default", "", "", "", "", "0", "0", "", "", 0]);
    '
fi

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
(cd "${PUBLIC_DIR}" && TYPO3_CONTEXT=Development PHP_CLI_SERVER_WORKERS=4 \
    php -S "${HOST}" -t "${PUBLIC_DIR}" "${WORK_DIR}/router.php" > "${WORK_DIR}/php-server.log" 2>&1) &
SERVER_PID=$!
if [ "${SETUP}" = "minimal" ]; then
    trap 'kill ${SERVER_PID} 2>/dev/null || true' EXIT
fi
for _ in $(seq 1 30); do
    curl --silent --output /dev/null "${BASE_URL}" && break
    sleep 1
done

step "Check the frontend and the backend"
check_url "The frontend" "${BASE_URL}"
check_url "The backend login" "${BASE_URL}typo3/"

if [ "${SETUP}" = "devbox" ]; then
    step "TYPO3 ${TYPO3_VERSION} in classic mode with the devbox is running on ${BASE_URL} (PID ${SERVER_PID})"
    exit 0
fi

step "Build the URL list"
URLS="$(typo3 crawler:buildQueue 1 default --depth 0 --mode url)"
echo "${URLS}"
grep -qF "${BASE_URL}" <<< "${URLS}" || fail "crawler:buildQueue --mode url lists no URL of ${BASE_URL}"

step "Queue the URLs and crawl them with the message bus"
typo3 crawler:buildQueue 1 default --depth 0 --mode queue
typo3 messenger:consume crawler_high crawler crawler_low --limit 1 --time-limit 60 -vv
sql '
    $rows = $pdo->query("SELECT qid, exec_time, result_data FROM tx_crawler_queue")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
    $crawled = array_filter($rows, static fn (array $row): bool => (int) $row["exec_time"] > 0 && $row["result_data"] !== "");
    if ($crawled === []) {
        fwrite(STDERR, "::error::No queue entry was crawled by messenger:consume\n");
        exit(1);
    }
    foreach ($crawled as $row) {
        $data = json_decode((string) $row["result_data"], true);
        $result = is_array($data) ? json_decode((string) ($data["content"] ?? ""), true) : null;
        if (!is_array($result) || !empty($result["errorlog"])) {
            fwrite(STDERR, "::error::Queue entry " . $row["qid"] . " was not crawled successfully\n");
            exit(1);
        }
    }
'

step "Crawl the URLs right away"
typo3 crawler:flushQueue all
EXEC_OUTPUT="$(typo3 crawler:buildQueue 1 default --depth 0 --mode exec)"
echo "${EXEC_OUTPUT}"
grep -q 'OK:' <<< "${EXEC_OUTPUT}" || fail "crawler:buildQueue --mode exec did not crawl successfully"

step "TYPO3 ${TYPO3_VERSION} in classic mode works with the crawler extension"
