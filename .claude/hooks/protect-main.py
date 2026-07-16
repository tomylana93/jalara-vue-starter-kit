#!/usr/bin/env python3
"""PreToolUse hook (Bash matcher): best-effort local guard for the branch
rules in .ai/guidelines/02-ai-workflow.md §11 and docs/development-workflow.md.

Blocks:
  - `git commit` while main is checked out;
  - `git push` targeting main or dev: named refspecs (including quoted and
    refs/heads/* and src:dst forms), `--repo=...`, `--all`/`--branches`/
    `--mirror`, `--delete`, and bare `git push` while main or dev is
    checked out;
  - the same invocations nested inside `sh -c "..."` / `bash -c "..."`.

Committing on dev is allowed with a reminder: 02 §1 rule 9 delegates that
decision to the developer, who has given standing approval for direct dev
commits. Pushes to dev still require a pull request.

This hook is defense-in-depth for agent sessions, not the enforcement
boundary: GitHub branch protection remains the primary control. A command
the lexer cannot parse safely is blocked when it looks like a push at a
protected ref (failing closed beats failing open).
"""

import json
import os
import re
import shlex
import subprocess
import sys

SHELL_OPS = {";", "&&", "||", "|", "&", ";;", "|&"}
GIT_GLOBAL_VALUE_OPTS = {"-c", "--exec-path", "--git-dir", "--work-tree", "--namespace"}
PUSH_VALUE_OPTS = {"-o", "--push-option", "--repo", "--receive-pack", "--exec"}
PUSH_ALL_OPTS = {"--all", "--branches", "--mirror"}


def current_branch(repo: str) -> str:
    try:
        out = subprocess.run(
            ["git", "-C", repo, "branch", "--show-current"],
            capture_output=True, text=True, timeout=5,
        )
        return out.stdout.strip()
    except Exception:
        return ""


def ref_target(token: str):
    m = re.search(r"(?:^|[:/+])(main|dev)$", token)
    return m.group(1) if m else None


def block_push(target: str):
    return (2, f"Blocked: pushing to {target} is forbidden — main and dev change "
               "only through pull requests (branch protection; "
               ".ai/guidelines/02-ai-workflow.md §11).")


def check(tokens, default_repo, notes):
    """Scan one token stream; return (exit_code, message)."""
    i, n = 0, len(tokens)
    while i < n:
        t = tokens[i]

        if t in ("sh", "bash", "zsh", "dash") or t.endswith(("/sh", "/bash", "/zsh", "/dash")):
            j = i + 1
            while j < n and tokens[j] not in SHELL_OPS:
                if tokens[j] == "-c" and j + 1 < n:
                    code, msg = scan_command(tokens[j + 1], default_repo, notes)
                    if code:
                        return code, msg
                j += 1
            i += 1
            continue

        if not (t == "git" or t.endswith("/git")):
            i += 1
            continue

        # Skip git global options to find the subcommand; honor -C for the
        # repository the branch check runs against.
        i += 1
        repo = default_repo
        subcmd = None
        while i < n:
            t = tokens[i]
            if t in SHELL_OPS:
                break
            if t == "-C" and i + 1 < n:
                repo = tokens[i + 1]
                i += 2
                continue
            if t in GIT_GLOBAL_VALUE_OPTS:
                i += 2
                continue
            if t.startswith("-"):
                i += 1
                continue
            subcmd = t
            i += 1
            break
        if subcmd is None:
            continue
        branch = current_branch(repo)

        if subcmd == "commit":
            if branch == "main":
                return (2, "Blocked: direct commits on main are forbidden. Use the "
                           "release flow in docs/development-workflow.md "
                           "(.ai/guidelines/02-ai-workflow.md §11).")
            if branch == "dev":
                notes.append("Note: committing directly on dev. The guideline prefers "
                             "a task branch + PR unless the developer directed otherwise.")

        elif subcmd == "push":
            bare = 0
            repo_opt = False
            tags_only = False
            target = None
            while i < n:
                t = tokens[i]
                if t in SHELL_OPS:
                    break
                if t == "--tags":
                    tags_only = True
                    i += 1
                    continue
                if t in PUSH_VALUE_OPTS:
                    if t == "--repo":
                        repo_opt = True
                    i += 2
                    continue
                if t.startswith("--repo="):
                    repo_opt = True
                    i += 1
                    continue
                if t in PUSH_ALL_OPTS:
                    return block_push("all branches (includes main and dev)")
                if t.startswith("-"):
                    i += 1
                    continue
                bare += 1
                # The first bare argument is the remote — unless --repo
                # already named it, in which case every bare arg is a refspec.
                if repo_opt or bare >= 2:
                    m = ref_target(t)
                    if m:
                        target = m
                i += 1
            if target is None and not tags_only and bare < 2 and not (repo_opt and bare >= 1):
                # Bare push sends the current branch (push.default simple).
                if branch in ("main", "dev"):
                    target = branch
            if target:
                return block_push(target)

    return 0, ""


def scan_command(cmd: str, default_repo: str, notes):
    try:
        lex = shlex.shlex(cmd, posix=True, punctuation_chars=True)
        lex.whitespace_split = True
        lex.commenters = ""
        tokens = list(lex)
    except ValueError:
        # Unparseable (e.g. unbalanced quotes): fail closed for push-like
        # commands touching protected names, allow everything else.
        if re.search(r"\bgit\b", cmd) and re.search(r"\bpush\b", cmd) and \
                re.search(r"\b(main|dev)\b|--all\b|--branches\b|--mirror\b", cmd):
            return (2, "Blocked: could not safely parse this command, and it looks "
                       "like a git push involving a protected branch. Rewrite it "
                       "unambiguously (branch protection also applies remotely).")
        return 0, ""
    return check(tokens, default_repo, notes)


def main() -> int:
    try:
        data = json.load(sys.stdin)
    except Exception:
        return 0
    cmd = ((data.get("tool_input") or {}).get("command")) or ""
    if "git" not in cmd:
        return 0
    repo = os.environ.get("CLAUDE_PROJECT_DIR") or os.getcwd()
    notes = []
    code, msg = scan_command(cmd, repo, notes)
    for note in notes:
        print(note, file=sys.stderr)
    if code:
        print(msg, file=sys.stderr)
    return code


if __name__ == "__main__":
    sys.exit(main())
