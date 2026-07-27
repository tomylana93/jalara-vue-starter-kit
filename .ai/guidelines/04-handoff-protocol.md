# Agent Handoff Protocol

This guideline defines the task-local protocol used when a developer transfers work between AI CLI agents. It complements the generated repository instructions and Superpowers skills; it does not duplicate them.

## Core principles

1. Every handoff is self-contained and must not depend on prior chat context.
2. Generated instructions in `AGENTS.md` or `CLAUDE.md` remain authoritative.
3. Task-local packets may add context and authority but may not weaken repository rules.
4. Evidence and next-agent instructions are separate sections.
5. Only the orchestrator may change scope, acceptance criteria, classification, ownership, or required approvals.
6. A reviewer remains read-only and must not silently fix findings.
7. Git state and command results are evidence; confidence statements are not.
8. Task packets, review findings, progress notes, and next-agent prompts must not be stored in Serena memory.

## Required identity and Git state

Every task packet and handoff declares:

- schema version;
- task ID;
- handoff ID when applicable;
- workflow status;
- source role and provider;
- target role and provider when assigned;
- fixed-point commit;
- expected current commit;
- base branch, task branch, and worktree;
- exact review diff range;
- write access and ownership boundary.

A receiving agent verifies these values before acting. Stop when the fixed point is stale, the expected commit cannot be reproduced, the branch or worktree differs, the diff range is invalid, or authority is ambiguous.

## Workflow status

Use exactly one of:

- `PLANNED`
- `AWAITING_HUMAN_APPROVAL`
- `READY_FOR_WRITER`
- `IMPLEMENTING`
- `READY_FOR_REVIEW`
- `CHANGES_REQUESTED`
- `READY_FOR_INTEGRATION`
- `GATE_FAILED`
- `HUMAN_DECISION_REQUIRED`
- `COMPLETE`
- `BLOCKED`
- `CANCELLED`

## Task packet

The orchestrator produces a task packet containing:

- goal;
- measurable acceptance criteria;
- non-goals;
- classification;
- fixed point;
- active assignment;
- required Superpowers skills;
- required MCP tools;
- write authority and ownership;
- targeted checks and finishing gate;
- approvals;
- stop conditions;
- expected output contract.

Provider preference must not override role, risk, required tools, task classification, independence requirements, or provider availability.

## Completion evidence

Every agent reports:

- scope completed;
- files and symbols inspected or changed;
- references verified;
- tests added or updated;
- exact commands actually run and their outcomes;
- checks passed, failed, or not run;
- decisions and assumptions;
- known risks and unresolved issues;
- Serena memories changed, or `none`;
- whether ownership was respected;
- whether human approval is required.

Never report a command as passed unless it was executed successfully in the current repository state.

## Next-agent assignment

Every handoff that continues the workflow includes a next-agent assignment with:

- target role;
- write access;
- owned and forbidden paths or symbols;
- required skills and MCP tools;
- exact task objective;
- acceptance criteria;
- relevant prior evidence;
- required inspection and commands;
- output contract;
- stop conditions.

The next-agent prompt begins by directing the agent to follow `AGENTS.md` or `CLAUDE.md`, then states only task-local context and authority.

## Review handoff

A writer hands off an exact `base_sha..head_sha` diff to a reviewer. The reviewer verifies the diff, remains read-only, and returns a decision and structured findings defined in `.ai/guidelines/05-review-rubric.md`.

The reviewer may route to:

- the writer when changes are requested;
- the integrator when approved;
- the orchestrator or developer when blocked or when a material decision is unresolved.

## Finding resolution

Each finding keeps a stable ID. The fix writer records one decision:

- `accepted`;
- `rejected` with repository evidence;
- `deferred` with explicit human approval and residual risk.

Blocking findings may not disappear from later handoffs. They require an explicit resolution record.

## Review-cycle limits

Default limits:

- two review-and-fix cycles;
- three debugging attempts for the same failure;
- one architecture replan unless the developer approves another.

When the limit is reached, set status to `HUMAN_DECISION_REQUIRED` and report the unresolved decision, evidence, and options. Do not loop blindly.

## Sensitive data

Handoffs must not contain secrets, credentials, tokens, unnecessary personal data, production data extracts, or private customer information. Use references and summaries instead of copying sensitive tool output.

## Runtime storage

Use `.ai/runs/<task-id>/` for temporary local packets and handoffs. The directory is ignored by Git except for its documentation files. Durable project decisions belong in source code, committed documentation, ADRs, or approved Serena memories; temporary coordination does not.