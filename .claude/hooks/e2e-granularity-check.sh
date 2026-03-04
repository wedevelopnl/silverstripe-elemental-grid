#!/usr/bin/env bash
# E2E test granularity check — warns when a spec file being written to tests/E2E/specs/
# contains only a single test() block, suggesting it may be a functional test disguised as E2E.
set -euo pipefail

input="$(cat)"
tool="$(echo "$input" | jq -r '.tool // empty')"

# Only check Write and Edit tools
if [[ "$tool" != "Write" && "$tool" != "Edit" ]]; then
  exit 0
fi

# Get the file path from tool input
file_path="$(echo "$input" | jq -r '.tool_input.file_path // .tool_input.filePath // empty')"

# Only check files in tests/E2E/specs/
if [[ ! "$file_path" =~ tests/E2E/specs/.*\.spec\.ts$ ]]; then
  exit 0
fi

# For Write tool, check the content being written
if [[ "$tool" == "Write" ]]; then
  content="$(echo "$input" | jq -r '.tool_input.content // empty')"

  # Count test() blocks in the content
  test_count=$(echo "$content" | grep -cE '^\s*test\(' || true)

  if [[ "$test_count" -le 1 ]]; then
    cat >&2 << 'EOF'
⚠️  E2E Test Granularity Warning

This spec has only one test() block. E2E tests should validate complete user journeys
with multiple steps, not single operations. Consider:

- Does this test cover a full user story (3+ distinct user actions)?
- Could this be better tested at the unit or integration level?
- Should this be combined with related operations into a user journey spec?

See the e2e-conventions instructions for the project's E2E testing philosophy.
EOF
  fi
fi

# Always allow — this is a warning, not a blocker
exit 0
