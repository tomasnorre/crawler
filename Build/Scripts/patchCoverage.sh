#!/usr/bin/env bash

#
# Checks that every executable line changed in Classes/ is covered by the unit or functional tests (patch coverage).
# The overall coverage stays as it is, but every contribution has to come with tests for the code it changes.
# See https://andreas.heigl.org/2022/08/28/increase-code-coverage-successively/
#
# Usage: Build/Scripts/patchCoverage.sh [base] [coverage file]
#   base           Commit to compare HEAD with, the changes since the merge base are checked, default "origin/main"
#   coverage file  Merged coverage of the unit and functional tests in PHP format, default ".Logs/coverage.cov",
#                  created by `composer test:coverage`
#
# Exits with 0 when all changed executable lines are covered, or when no executable lines were changed,
# and with 1 when changed lines are not covered. Those lines are listed.
#

set -euo pipefail

base="${1:-origin/main}"
coverage="${2:-.Logs/coverage.cov}"
patch=".Logs/patch.diff"

if [ ! -f "$coverage" ]; then
    echo "Coverage file $coverage not found, run \`composer test:coverage\` first." >&2
    exit 255
fi

mkdir -p "$(dirname "$patch")"
git diff "$base"...HEAD -- Classes > "$patch"

status=0
.Build/bin/phpcov patch-coverage --path-prefix "$PWD/" "$coverage" "$patch" || status=$?

# phpcov exits with 2 when the patch changes no executable lines, fx. only documentation or tests
if [ "$status" -eq 2 ]; then
    echo "No executable lines in Classes/ were changed."
    exit 0
fi

exit "$status"
