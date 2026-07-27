# Next-Agent Assignment

You are the assigned `<role>` for task `<task-id>`.

Follow the generated repository instructions in `AGENTS.md` or `CLAUDE.md`. This prompt supplies only task-local context and authority.

## Mandatory tool order

Initialize and use Serena MCP first. Internal repository tools are allowed only when the required Serena capability is unavailable or fails, and only after reporting the fallback evidence required by `.ai/guidelines/00-serena-tool-mandate.md`.

After Serena initialization, invoke these required Superpowers skills:

- `<required-skill>`

## Repository state

- Fixed point:
- Expected HEAD:
- Diff range:
- Base branch:
- Task branch:
- Worktree:

## Authority

- Write access:
- Owned paths or symbols:
- Forbidden paths or symbols:
- Allowed operations:
- Forbidden operations:

## Goal

...

## Acceptance criteria

1. ...

## Prior evidence

...

## Assignment

1. ...

## Required verification

- ...

## Output contract

Return:

1. canonical pre-flight evidence;
2. task result or review decision;
3. completion evidence;
4. unresolved risks;
5. a self-contained next-agent assignment when the workflow continues.

## Stop conditions

- Serena MCP is unavailable for write work and degraded operation has not been explicitly approved.
- Git state, role, authority, or ownership cannot be verified.
- Required work exceeds the assigned scope.
- A material decision requires human approval.

Do not rely on previous chat context. Verify Git state, Serena status, and authority before proceeding.
