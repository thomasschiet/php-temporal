#!/usr/bin/env bash
# Ralph loop — continuously runs Claude Code agents until the project is done.
# Inspired by: https://www.anthropic.com/engineering/building-c-compiler

set -euo pipefail

LOGDIR="agent_logs"
mkdir -p "$LOGDIR"

echo "Starting ralph loop for php-temporal..."
echo "Logs will be written to ./$LOGDIR/"
echo "Press Ctrl+C to stop."
echo ""

while true; do
    COMMIT=$(git rev-parse --short=6 HEAD 2>/dev/null || echo "no-git")
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    LOGFILE="$LOGDIR/agent_${TIMESTAMP}_${COMMIT}.log"

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting agent session → $LOGFILE"

    claude --dangerously-skip-permissions \
        -p "$(cat AGENT_PROMPT.md)" \
        --model claude-sonnet-4-6 \
        &> "$LOGFILE"

    EXIT_CODE=$?

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Session ended (exit $EXIT_CODE)"

    # Stop if PROGRESS.md signals completion
    if grep -qi "all tasks complete\|done\|finished" PROGRESS.md 2>/dev/null; then
        echo "PROGRESS.md indicates completion. Stopping ralph loop."
        break
    fi

    # Brief pause between sessions
    sleep 2
done

echo "Ralph loop finished."
