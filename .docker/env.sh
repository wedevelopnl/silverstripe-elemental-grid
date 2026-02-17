#!/bin/sh
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
WORKTREE_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DIR_NAME="$(basename "$WORKTREE_DIR")"

HASH=$(printf '%s' "$DIR_NAME" | cksum | awk '{print $1}')
OFFSET=$((HASH % 1000))

WEB_PORT=$((8000 + OFFSET))
DB_PORT=$((13000 + OFFSET))

cat > "$SCRIPT_DIR/.env" <<EOF
COMPOSE_PROJECT_NAME=${DIR_NAME}
WEB_PORT=${WEB_PORT}
DB_PORT=${DB_PORT}
EOF

echo "Generated .docker/.env:"
echo "  COMPOSE_PROJECT_NAME=${DIR_NAME}"
echo "  WEB_PORT=${WEB_PORT}"
echo "  DB_PORT=${DB_PORT}"
