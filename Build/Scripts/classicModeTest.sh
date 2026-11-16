#!/usr/bin/env bash

#
# Installs TYPO3 in classic mode (without Composer) with the crawler extension as it is packaged for the TER,
# and checks that the crawler works: its commands are available, it builds a queue and crawls it,
# directly and through the message bus.
#
# The extension is taken from the committed state (HEAD), packaged with `git archive`,
# so files marked with export-ignore in .gitattributes are left out, like in the TER package.
#
# Usage: Build/Scripts/classicModeTest.sh [TYPO3 version] [working directory]
#   TYPO3 version      Version to download from get.typo3.org, fx. "14" for the latest 14.x or "14.3.0", default "14"
#   working directory  Where TYPO3 is installed, must not exist yet, default: a new temporary directory
#
# Needs PHP with pdo_sqlite, curl, tar and git. TYPO3 is served by the PHP built-in web server on port 8080.
#

set -euo pipefail

TYPO3_VERSION="${1:-14}"
WORK_DIR="${2:-$(mktemp -d)}"
EXTENSION_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HOST="127.0.0.1:8080"
BASE_URL="http://${HOST}/"

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

# Runs PHP code with a PDO connection to the SQLite database of the installation as $pdo
sql() {
    php -r '
        $settings = require $argv[1] . "/typo3conf/system/settings.php";
        $pdo = new PDO("sqlite:" . $settings["DB"]["Connections"]["Default"]["path"]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        eval($argv[2]);
    ' "${PUBLIC_DIR}" "$1"
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

step "Set up TYPO3"
TYPO3_SETUP_ADMIN_PASSWORD="$(php -r 'echo bin2hex(random_bytes(8));')Aa1!"
export TYPO3_SETUP_ADMIN_PASSWORD
typo3 setup \
    --no-interaction \
    --force \
    --driver=sqlite \
    --admin-username=admin \
    --admin-email=admin@example.com \
    --project-name="Crawler classic mode" \
    --create-site="${BASE_URL}" \
    --server-type=other

step "Activate the crawler extension"
typo3 extension:activate info
typo3 extension:activate crawler
typo3 extension:setup
typo3 cache:flush
EXTENSIONS="$(typo3 extension:list)"
grep -qw crawler <<< "${EXTENSIONS}" || fail "The crawler extension is not active"

step "Check the crawler commands"
# Symfony leaves out commands it cannot create, so call them directly to show the reason
typo3 crawler:buildQueue --help -vvv || fail "crawler:buildQueue cannot be run"
typo3 crawler:flushQueue --help -vvv || fail "crawler:flushQueue cannot be run"
COMMANDS="$(typo3 list --raw)"
grep -q '^crawler:buildQueue ' <<< "${COMMANDS}" || fail "crawler:buildQueue is not listed"
grep -q '^crawler:flushQueue ' <<< "${COMMANDS}" || fail "crawler:flushQueue is not listed"

step "Add a crawler configuration to the root page"
sql '
    $pdo->prepare("INSERT INTO tx_crawler_configuration
        (pid, tstamp, crdate, name, configuration, base_url, pidsonly, exclude, begroups, fegroups,
         processing_instruction_filter, processing_instruction_parameters_ts, force_ssl)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, time(), time(), "default", "", "", "", "", "0", "0", "", "", 0]);
'

step "Start the web server on ${BASE_URL}"
(cd "${PUBLIC_DIR}" && php -S "${HOST}" index.php > "${WORK_DIR}/php-server.log" 2>&1) &
SERVER_PID=$!
trap 'kill ${SERVER_PID} 2>/dev/null || true' EXIT
for _ in $(seq 1 30); do
    curl --silent --output /dev/null "${BASE_URL}" && break
    sleep 1
done

step "Check the frontend and the backend"
FRONTEND_STATUS="$(curl --silent --output /dev/null --write-out '%{http_code}' "${BASE_URL}")"
[ "${FRONTEND_STATUS}" = "200" ] || fail "The frontend returned ${FRONTEND_STATUS}"
BACKEND_STATUS="$(curl --silent --location --output /dev/null --write-out '%{http_code}' "${BASE_URL}typo3/")"
[ "${BACKEND_STATUS}" = "200" ] || fail "The backend login returned ${BACKEND_STATUS}"

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
