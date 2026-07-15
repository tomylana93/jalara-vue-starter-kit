# Agent Task Packet

Use this contract before implementation begins. Store the completed packet in the GitHub issue or pull request that is the task's system of record.

```yaml
task:
  id: GH-000
  title: ""
  mode: express|standard|critical
  source:
    type: github_issue|pull_request|user_request
    reference: ""

repository:
  base_branch: dev
  working_branch: ""
  fixed_point: origin/dev
  clean_worktree_required: true

scope:
  goal: ""
  non_goals: []
  allowed_areas: []
  forbidden_areas: []

acceptance_criteria: []

risk:
  score: 0
  factors:
    ambiguity: 0
    blast_radius: 0
    data_schema: 0
    security_auth: 0
    behavior: 0
    reversibility: 0
  critical_overrides: []

routing:
  orchestrator: ""
  writer: ""
  reviewer: ""
  fallback_writer: ""

context:
  required_skills: []
  required_mcp: []
  documentation_queries: []
  relevant_existing_patterns: []

verification:
  targeted_tests: []
  required_gates: []
  manual_checks: []

approvals:
  plan_required: false
  migration_required: false
  dependency_change_required: false
  merge_required: true
```

## Rules

- Resolve facts from the repository before asking the user.
- Do not start implementation while required decisions are unresolved.
- Keep scope explicit and reject unrelated changes.
- The writer and final reviewer must be different providers for standard and critical work.
- Never include secrets, tokens, credentials, or personal data.
