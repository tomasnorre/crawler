#!/usr/bin/env bash

#
# Runs the functional tests with process isolation, several test classes at a time.
#
# Every test still gets its own process, so a test can't affect another one. Test classes can run at the same time, as
# the testing framework gives each of them its own database and instance directory. The tests of a class run one after
# another.
#
# Usage: Build/Scripts/functionalTestsParallel.sh [jobs]
#   jobs  Number of test classes running at the same time, default is the number of CPU cores
#
# The database is configured with the environment variables of the testing framework (typo3DatabaseDriver, …).
#
# Exits with 0 when the tests of all classes pass, and with 1 otherwise. The output of the failed classes is printed at the end.
#

set -euo pipefail

jobs="${1:-$(nproc)}"
logs="$(mktemp -d)"
trap 'rm -rf "$logs"' EXIT

# The processes would create these directories at the same time
mkdir -p .Build/Web/typo3temp/var/tests/functional-sqlite-dbs .Build/Web/typo3temp/var/transient

run_class() {
    local log="$1/${2//\//_}.log"
    local status=0

    .Build/bin/phpunit --colors=always -c Tests/FunctionalTests.xml --process-isolation "$2" > "$log" 2>&1 || status=$?

    if [ "$status" -ne 0 ]; then
        echo "$2" >> "$1/failed"
        echo "FAILED $2 (exit code $status)"
    else
        printf 'passed %s: %s\n' "$2" "$(grep -E 'Tests: |OK \(' "$log" | tail -n 1)"
    fi
}
export -f run_class

# The largest classes first, so that a long-running one doesn't start last
# (wc, as the find of the container images can't print the size)
find Tests/Functional -name '*Test.php' -exec wc -c {} \; | sort -rn | sed 's/^ *[0-9]* //' > "$logs/classes"

if [ ! -s "$logs/classes" ]; then
    echo "No functional tests found." >&2
    exit 1
fi

xargs -P "$jobs" -I {} bash -c 'run_class "$1" "$2"' _ "$logs" {} < "$logs/classes"

if [ -e "$logs/failed" ]; then
    # Not printed by the processes, to not mix the output of the classes
    sort "$logs/failed" | while read -r class; do
        printf '\nOutput of %s:\n\n' "$class"
        cat "$logs/${class//\//_}.log"
    done
    printf '\nFailed test classes:\n'
    sort "$logs/failed"
    exit 1
fi

# Every class has to have a result, also when a process got lost
if [ "$(find "$logs" -name '*.log' | wc -l)" -ne "$(wc -l < "$logs/classes")" ]; then
    echo "Not every test class was run." >&2
    exit 1
fi

printf '\nAll %s test classes passed.\n' "$(wc -l < "$logs/classes")"
