#!/bin/bash
# Count mutations per file from infection.log

INFECTION_LOG_FILE="infection.log"  # adjust if needed

grep -E '^[0-9]+\)' "$INFECTION_LOG_FILE" | grep -o 'Classes/[^:]*' | sort | uniq -c | sort -nr
