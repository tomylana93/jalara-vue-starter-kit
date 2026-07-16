#!/usr/bin/env bash
# PreToolUse hook (Bash matcher): enforce .ai/guidelines/02-ai-workflow.md §11.
# Blocks `git commit` while on main and any `git push` targeting main.
# Committing on dev is allowed but gets a reminder that the guideline prefers task branch + PR.
set -u

input=$(cat)
cmd=$(printf '%s' "$input" | jq -r '.tool_input.command // empty' 2>/dev/null) || exit 0
[ -z "$cmd" ] && exit 0
case "$cmd" in
    *git*) ;;
    *) exit 0 ;;
esac

repo_dir=${CLAUDE_PROJECT_DIR:-$PWD}
branch=$(git -C "$repo_dir" branch --show-current 2>/dev/null)
[ -z "$branch" ] && exit 0

if printf '%s' "$cmd" | grep -Eq 'git([[:space:]]+-[^[:space:]]+)*[[:space:]]+commit'; then
    if [ "$branch" = "main" ]; then
        echo "Blocked: direct commits on main are forbidden. Use the release flow in docs/development-workflow.md (see .ai/guidelines/02-ai-workflow.md §11)." >&2
        exit 2
    fi
    if [ "$branch" = "dev" ]; then
        echo "Note: committing directly on dev. The guideline prefers a task branch + PR unless the developer directed otherwise." >&2
    fi
fi

if printf '%s' "$cmd" | grep -Eq 'git([[:space:]]+-[^[:space:]]+)*[[:space:]]+push'; then
    if [ "$branch" = "main" ] || printf '%s' "$cmd" | grep -Eq 'push[[:space:]].*([[:space:]]|:)main([[:space:]]|$)'; then
        echo "Blocked: pushing to main is forbidden. Releases go through the promotion PR dev -> main and Release Please." >&2
        exit 2
    fi
fi

exit 0
