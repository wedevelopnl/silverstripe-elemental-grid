#!/usr/bin/env bash
# Pre-push QA gate — intercepts `git push` commands and runs `make qa` first.
# Used as a Claude Code PreToolUse hook on the Bash tool.

set -euo pipefail

input="$(cat)"
command="$(echo "$input" | jq -r '.tool_input.command // empty')"

# Only intercept git push commands
if [[ ! "$command" =~ ^git\ push ]]; then
  exit 0
fi

echo "Pre-push QA gate: running make qa before push..." >&2

cd "${CLAUDE_PROJECT_DIR:-.}"

if ! make qa; then
  echo "QA failed — push blocked." >&2
  exit 2
fi
