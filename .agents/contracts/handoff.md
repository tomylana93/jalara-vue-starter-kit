# Agent Handoff

Use this contract whenever ownership moves between agents, providers, sessions, or worktrees. Publish the final handoff to the GitHub issue or pull request. A temporary local copy is only a cache.

```yaml
handoff:
  task_id: GH-000
  phase_completed: ""
  status: ""

  decisions:
    - decision: ""
      rationale: ""
      source: ""

  changes:
    files_modified: []
    files_created: []
    files_deleted: []

  evidence:
    documentation_consulted: []
    commands_run: []
    tests_passed: []
    gates_passed: []

  risks:
    known: []
    unresolved: []

  next:
    owner: ""
    action: ""
    fixed_point: ""
```

## Rules

- Report evidence, not confidence.
- Reference existing specs, plans, ADRs, issues, commits, and diffs instead of duplicating them.
- Identify unresolved risks explicitly.
- The receiving agent must verify the fixed point before continuing.
- Never include secrets, tokens, credentials, or personal data.
