#!/usr/bin/env bash
# PreToolUse hook (Bash matcher): local guard for the branch rules in
# .ai/guidelines/02-ai-workflow.md §11 and docs/development-workflow.md.
#
# Blocks:
#   - `git commit` while main is checked out;
#   - `git push` whose refspec targets main or dev, including
#     refs/heads/... and src:dst forms, and bare `git push` while
#     main or dev is checked out.
# Committing on dev is allowed with a reminder.
#
# This is defense-in-depth for agent sessions, not the enforcement
# boundary: GitHub branch protection remains the primary control.
set -u

input=$(cat)
cmd=$(printf '%s' "$input" | jq -r '.tool_input.command // empty' 2>/dev/null) || exit 0
[ -z "$cmd" ] && exit 0
case "$cmd" in
    *git*) ;;
    *) exit 0 ;;
esac

# Naive word split is acceptable here: quoted arguments are rare in git
# commands, a false block is recoverable, and a false allow is not.
read -ra tok <<< "$cmd"
n=${#tok[@]}

protected_target() {
    # Matches main/dev as a push target: "main", "refs/heads/dev",
    # "HEAD:main", "feature:refs/heads/main", "+main", ":dev" (delete).
    [[ "$1" =~ (^|[:/+])(main|dev)$ ]] && printf '%s' "${BASH_REMATCH[2]}"
}

i=0
while (( i < n )); do
    case "${tok[i]}" in
        git|*/git) ;;
        *) (( i++ )); continue ;;
    esac
    (( i++ ))

    # Skip git global options to find the subcommand; honor -C for the
    # repo the branch check runs against.
    repo_dir=${CLAUDE_PROJECT_DIR:-$PWD}
    sub=""
    while (( i < n )); do
        t=${tok[i]}
        case "$t" in
            -C) (( i + 1 < n )) && repo_dir=${tok[i + 1]}; (( i += 2 )) ;;
            -c|--exec-path|--git-dir|--work-tree|--namespace) (( i += 2 )) ;;
            -*) (( i++ )) ;;
            *) sub=$t; (( i++ )); break ;;
        esac
    done
    [ -z "$sub" ] && continue
    branch=$(git -C "$repo_dir" branch --show-current 2>/dev/null) || branch=""

    if [ "$sub" = "commit" ]; then
        if [ "$branch" = "main" ]; then
            echo "Blocked: direct commits on main are forbidden. Use the release flow in docs/development-workflow.md (.ai/guidelines/02-ai-workflow.md §11)." >&2
            exit 2
        fi
        if [ "$branch" = "dev" ]; then
            echo "Note: committing directly on dev. The guideline prefers a task branch + PR unless the developer directed otherwise." >&2
        fi
    elif [ "$sub" = "push" ]; then
        # First bare argument is the remote; later bare arguments are
        # refspecs. Options that consume a value are skipped with it.
        bare=0 target=""
        j=$i
        while (( j < n )); do
            t=${tok[j]}
            case "$t" in ';'|'&&'|'||'|'|'|'&') break ;; esac
            case "$t" in
                -o|--push-option|--repo|--receive-pack|--exec) (( j += 2 )); continue ;;
                -*) (( j++ )); continue ;;
            esac
            (( bare++ )) || true
            if (( bare >= 2 )); then
                m=$(protected_target "$t") && target=$m
            fi
            (( j++ ))
        done
        if [ -z "$target" ] && (( bare < 2 )); then
            # Bare push sends the current branch (push.default simple).
            case "$branch" in main|dev) target=$branch ;; esac
        fi
        if [ -n "$target" ]; then
            echo "Blocked: pushing to $target is forbidden — it changes only through pull requests (branch protection; .ai/guidelines/02-ai-workflow.md §11)." >&2
            exit 2
        fi
        i=$j
    fi
done

exit 0
