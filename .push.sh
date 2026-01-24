#!/usr/bin/env bash

# push.sh

set -euo pipefail

# ===============================================
# Настройки (можно менять)
# ===============================================

REMOTE_NAME="origin"
BRANCH="main"                # поменяй на master, если у тебя старая ветка
COMMIT_MSG="${1:-Update $(date '+%Y-%m-%d %H:%M:%S')}"

# ===============================================

echo "→ Добавляем все изменения..."
git add -A

echo "→ Создаём коммит: $COMMIT_MSG"
git commit -m "$COMMIT_MSG" || {
    echo "→ Нечего коммитить (нет изменений)"
    exit 0
}

echo "→ Пушим в $REMOTE_NAME/$BRANCH ..."
git push -u "$REMOTE_NAME" "$BRANCH"

echo ""
echo "Готово ✓"