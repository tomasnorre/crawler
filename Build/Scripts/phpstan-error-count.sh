#!/bin/bash
# Count ignored PHPStan errors per file from baseline.neon

BASELINE_FILE="phpstan-baseline.neon"  # adjust if needed

awk '/path:/{print $2}' "$BASELINE_FILE" | sort | uniq -c | sort -nr

