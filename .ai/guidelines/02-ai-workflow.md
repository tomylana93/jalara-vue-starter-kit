# AI Agent Workflow

This repository uses three AI providers as a coordinated engineering team:

* **OpenAI agent**: orchestration, architecture, planning, difficult debugging, CI, and release analysis.
* **Anthropic agent**: complex implementation, domain logic, security-sensitive work, and code review.
* **Google agent**: codebase scouting, frontend implementation, mechanical refactoring, and fast verification. It runs via the Antigravity CLI, which reads `AGENTS.md` — the same generated content as `CLAUDE.md`, so no separate instruction file is needed.

These assignments are defaults based on capability, not permanent rankings. The developer may override them for a specific task.

All agents share the same Serena MCP project. Session start, fixed-point discipline, memory rules, and the canonical pre-flight block are defined in `.ai/guidelines/01-serena.md` — this file does not restate them.

---

## 1. Core operating rules

Every task follows these rules:

1. One task has one **orchestrator**.
2. One working branch or worktree has one **writer** at a time.
3. Agents may research, inspect code, analyze tests, and review in parallel.
4. Two agents must not modify the same file, symbol, migration, route group, generated artifact, or dependency manifest concurrently.
5. Whenever a task requires independent review — always for Standard and Deep — the final reviewer must be different from the writer. Truly mechanical Routine work is the only exception (see §4).
6. Completion requires evidence from tests and gates, not an agent's confidence statement.
7. Do not expand scope to unrelated cleanup.
8. Do not commit directly to `main`. Do not push directly to `main` or `dev` — both change only through pull requests. GitHub branch protection is the enforcement boundary; a best-effort local guard (`.claude/hooks/protect-main.py`) additionally blocks the common direct-push and main-commit forms in agent sessions but must not be relied on as complete enforcement.
9. Permanent changes enter `dev` through a pull request unless the developer explicitly directs otherwise. In this repository the developer has given standing approval for direct commits on `dev`; the guard hook therefore reminds rather than blocks there. Pushing `dev` to the remote still requires a pull request.
10. Human approval is required for architecture, dependencies, destructive migrations, authentication, authorization, public contracts, and releases.

---

## 2. Agent roles

### Orchestrator

The orchestrator owns the task, not necessarily the implementation.

Responsibilities:

* understand the developer's request;
* inspect repository context before asking questions;
* classify the task;
* split work into non-overlapping assignments;
* select the writer and reviewers;
* define acceptance criteria;
* define file and symbol ownership;
* collect agent findings;
* resolve conflicting recommendations;
* control the final integration;
* report completion evidence.

Only one orchestrator is active for a task. The orchestrator remains read-only unless also explicitly assigned as the writer or integrator.

### Writer

The writer is the only agent allowed to modify the assigned branch or worktree.

Responsibilities:

* implement only the approved scope;
* follow existing repository patterns;
* write or update tests;
* run targeted checks during implementation;
* report changed files and unresolved risks;
* stop when the implementation requires an unapproved architectural decision.

The writer must not act as the final reviewer.

### Scout

Scouts are read-only agents that run in parallel.

Typical assignments: locate relevant symbols and call sites, identify existing patterns, inspect tests, inspect database structure, research framework documentation, analyze regression risks, propose implementation boundaries.

Scouts must not edit files, install components, change dependencies, update generated files, or create competing implementations on the writer's branch.

### Reviewer

Reviewers are read-only.

They inspect: acceptance-criteria compliance, regressions, security and data integrity, existing conventions, test coverage, unnecessary complexity, and scope expansion.

A reviewer returns findings to the writer. The reviewer does not silently fix the code.

### Integrator

The orchestrator normally acts as integrator.

Responsibilities:

* confirm all assignments used the same fixed-point commit;
* collect writer output and reviewer findings;
* apply or delegate approved fixes;
* run the canonical finishing gate;
* prepare the final handoff or pull request.

---

## 3. Provider routing

| Provider  | Primary strengths                                                              | Preferred roles                                                    |
| --------- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------ |
| OpenAI    | orchestration, architecture, difficult debugging, concurrency, CI and releases | orchestrator, architecture challenger, debugging writer, integrator |
| Anthropic | complex implementation, domain reasoning, security-sensitive code, deep review | backend writer, domain/security reviewer, architecture challenger   |
| Google    | fast scouting, frontend and UI work, mechanical refactoring, broad inspection  | frontend writer, scout, mechanical-refactor writer, UI reviewer     |

The safest default assignment:

```text
OpenAI       -> orchestrator and architecture
Anthropic    -> primary writer
Google       -> read-only scout and final frontend/repository reviewer
```

The actual assignment must follow task characteristics rather than provider preference. Google may perform Deep work only when the selected model has sufficient reasoning capability and the orchestrator explicitly assigns it — provider names alone do not determine permission; task risk and actual model capability do.

### Provider fallback

When a required independent provider is unavailable (quota, outage, not installed):

1. use a fresh session of an available provider as the independent reviewer — it must not have written the diff and must review read-only;
2. for Standard work, a different model or fresh session of the same provider counts as independent review;
3. for Deep work, at least two genuinely distinct perspectives are still required — obtain explicit developer approval before proceeding with fewer than two providers;
4. record the substitution and its residual risk in the pre-flight and handoff evidence;
5. never skip review entirely because the preferred provider is unavailable.

Tool and MCP unavailability (as opposed to provider unavailability) is handled in `.ai/guidelines/03-mcp-routing.md` §Degraded Operation.

---

## 4. Task classification

Each task is classified as `Routine`, `Standard`, or `Deep`.

### Routine

Use when all conditions apply:

* local and reversible;
* follows an established pattern;
* no schema, authentication, authorization, or dependency changes;
* limited blast radius;
* acceptance criteria are clear.

Examples: text or copy changes, isolated styling, simple component reuse, mechanical rename with known references, formatting or lint fixes, a small bug with an obvious cause.

Routine work may use one writer, one parallel scout or reviewer when useful, targeted tests, and the standard finishing gate. Routine tasks do not require the workflow to stop merely to ask for classification approval.

### Standard

Use when the task involves normal feature or bug work with moderate risk.

Examples: new CRUD following an existing pattern, additive migrations, frontend and backend changes in one feature, changes across several files, non-trivial validation, integration with an already installed package, refactoring behavior without changing architecture.

Standard work requires:

* one orchestrator;
* one writer;
* at least one independent reviewer from another provider;
* explicit acceptance criteria;
* targeted tests;
* full finishing gate.

### Deep

Use when any of these applies:

* new domain concept or module boundary;
* authentication or authorization changes;
* destructive migration or backfill;
* concurrency or race-condition work;
* new dependency;
* public API or external contract changes;
* new architectural pattern;
* release or deployment architecture;
* difficult-to-reverse decisions;
* unclear requirements with high blast radius.

Deep work requires:

1. repository discovery;
2. parallel analysis by at least two providers;
3. `brainstorming`;
4. written design or plan;
5. `/grill-me` or equivalent adversarial questioning;
6. human approval of material decisions;
7. one designated writer per implementation area;
8. test-first implementation;
9. independent cross-provider review;
10. full finishing gate;
11. explicit human approval before merge when required.

---

## 5. Parallel execution model

Parallelism is allowed only when assignments do not conflict.

### Safe parallel work

Agents may safely run these jobs concurrently: architecture analysis, existing-pattern discovery, documentation research, test-gap analysis, security review, frontend design analysis, database-schema inspection, CI or deployment analysis, read-only code review.

### Multiple parallel writers

Multiple writers are permitted only when all conditions are met:

1. each writer uses a separate Git branch and Git worktree;
2. all branches start from the same fixed-point commit;
3. ownership boundaries are explicit;
4. owned files and symbols do not overlap;
5. only one writer owns migrations, routes, generated files, dependencies, or shared configuration;
6. one integrator combines the results;
7. the full gate runs after integration.

Example of acceptable split:

```text
Writer A:
- app/Domain/Billing/**
- tests/Feature/Billing/**

Writer B:
- resources/js/pages/billing/**
- resources/js/components/billing/**

Integrator only:
- routes/**
- composer.json
- package.json
- pnpm-lock.yaml
- generated Wayfinder files
```

### Forbidden parallel writes

Never allow concurrent writers to modify:

* the same file, class, or Vue component;
* migrations;
* route files;
* `composer.json` or `composer.lock`;
* `package.json` or `pnpm-lock.yaml`;
* authentication configuration or authorization catalogs;
* generated Wayfinder files;
* shared types used by both frontend and backend;
* the same tests;
* the same Serena memory.

When ownership overlaps, convert all but one agent to read-only review mode.

---

## 6. Task startup

The orchestrator starts every task with:

```text
Task:
- Classification: Routine|Standard|Deep
- Goal:
- Acceptance criteria:
- Non-goals:
- Fixed point:
- Orchestrator:
- Writer:
- Scouts:
- Reviewer:
- Write ownership:
- Required skills:
- Required MCP:
- Required tests:
- Required approvals:
```

Agents do not need to stop immediately after classification unless:

* a material requirement is missing;
* an architectural decision requires developer approval;
* the requested scope conflicts with repository policy;
* the workspace contains unrelated changes;
* safe file ownership cannot be established.

Repository facts must be inspected before asking the developer questions.

Before the first modification, every writer posts the canonical pre-flight block defined in `.ai/guidelines/01-serena.md` §Pre-Flight Evidence. Scouts and reviewers use the same block with `Write access: no`.

---

## 7. Deep workflow

### Phase 1: Parallel discovery

The orchestrator assigns read-only investigations.

Recommended split:

```text
OpenAI:
- architecture and cross-cutting impact.

Anthropic:
- domain invariants, security, and backend risks.

Google:
- frontend impact, existing UI patterns, and mechanical change map.
```

Each agent returns: repository evidence, affected symbols, risks, alternatives, recommendation, unresolved decisions.

### Phase 2: Design

The orchestrator combines findings into one design.

The design must identify: problem, current behavior, proposed behavior, data model impact, backend boundaries, frontend boundaries, authorization impact, failure cases, testing strategy, migration and rollback strategy, rejected alternatives.

Conflicts between providers must be presented explicitly, not averaged into vague prose.

### Phase 3: Grill

Use `/grill-me` to test: hidden assumptions, edge cases, data integrity, security boundaries, concurrency, rollback, operational failure, maintenance cost.

Material unresolved decisions require developer approval.

### Phase 4: Implementation assignment

The orchestrator defines: one primary writer, optional isolated parallel writers, exact file and symbol ownership, integration order, targeted tests, review assignments.

### Phase 5: Implementation

Writers use TDD:

1. write or update the failing test;
2. confirm the expected failure;
3. implement the smallest correct change;
4. run the targeted test;
5. refactor while green;
6. report evidence.

### Phase 6: Independent review

A different provider reviews the fixed diff.

The reviewer reports each finding as:

```text
Finding:
- Severity: P0|P1|P2|P3
- Category:
- File/symbol:
- Requirement:
- Evidence:
- Impact:
- Recommended fix:
```

Preferences without concrete impact are not findings.

### Phase 7: Integration and gate

The integrator resolves accepted findings and runs the full finishing gate.

---

## 8. Routine and Standard workflows

### Routine workflow

```text
1. Classify.
2. Inspect the existing pattern.
3. Assign one writer.
4. Implement with the smallest relevant test.
5. Run targeted checks.
6. Run finishing gate.
7. Summarize evidence.
```

A separate reviewer is optional for truly mechanical Routine work only — this is the single exception to core rule 5.

### Standard workflow

```text
1. Classify.
2. Run parallel read-only discovery where useful.
3. Define acceptance criteria and scope.
4. Assign one writer.
5. Implement test-first.
6. Run targeted checks.
7. Obtain review from another provider.
8. Resolve findings.
9. Run finishing gate.
10. Prepare handoff or pull request.
```

---

## 9. Finishing gate

Run in this order:

```text
Finishing gate:
[ ] php artisan wayfinder:generate --with-form --no-interaction
    when routes, controllers, or invokable actions changed — run first so
    the generated files pass the formatting and lint steps below
[ ] vendor/bin/pint --dirty --format agent
[ ] pnpm run lint
[ ] pnpm run format
[ ] targeted tests passed
[ ] composer run agent:gate passed
    (runs ci:check — lint:check, format:check, types:check, tests —
    plus pnpm run build)
[ ] reviewer findings resolved or explicitly accepted
[ ] no unrelated files changed
[ ] Serena memory updated or not needed with reason
[ ] final diff reviewed against fixed point
[ ] commit or pull request explicitly requested
```

Run Wayfinder generation when any of these change: `routes/*.php`, controllers, invokable actions, route names or parameters. Generated files have one owner: the integrator or designated backend writer.

### Gate failure

Safe automatic fixes: Pint, ESLint fix, Prettier.

For logic, PHPStan, type, or test failures:

1. use `systematic-debugging`;
2. identify root cause;
3. make a targeted fix;
4. rerun the smallest failing check;
5. rerun the full gate.

Stop after two or three unsuccessful cycles when the same failure remains, the required fix changes architecture, scope would expand, or another writer owns the affected area. Do not loop blindly.

---

## 10. Handoff format

Every writer returns:

```text
Handoff:
- Task:
- Provider:
- Role:
- Fixed point or final commit:
- Branch/worktree:
- Scope completed:
- Files changed:
- Symbols inspected/modified:
- References verified:
- Tests added or updated:
- Commands run:
- Checks passed:
- Decisions made:
- Known risks:
- Unresolved issues:
- Serena memories changed:
- Ownership boundary respected: yes
- Recommended next owner:
```

Every receiving agent verifies the fixed point and current diff before continuing.

---

## 11. Git and branch rules

The canonical release process is documented in `docs/development-workflow.md`. Summary of the normal flow:

```text
dev
  -> task branch (or provider worktree branch; integration branch when
     multiple writers are used)
  -> squash PR into dev (Conventional Commit title)
  -> promotion merge-commit PR dev -> main
  -> Release Please PR into main (owns CHANGELOG.md, version, tag, release)
  -> merge-commit sync PR main -> dev
```

Rules:

* never let two writers share one working tree;
* never use `dev` or `main` as an experimental writer branch;
* parallel writers branch from the same fixed point;
* the integrator owns conflict resolution;
* do not hide conflicts by accepting both implementations;
* temporary PR titles follow Conventional Commits (`feat` bumps minor, `fix` bumps patch, `!`/`BREAKING CHANGE` bumps major);
* direct pushes to `main` and `dev` are forbidden; branch protection enforces this;
* Release Please owns `CHANGELOG.md`, version metadata, tags, and GitHub Releases — never create these manually;
* published `v*` tags are immutable; correct a bad release with a new patch version;
* hotfixes flow `main -> hotfix/* -> main`, then synchronize `main -> dev`;
* releases remain developer-controlled.

---

## 12. Completion definition

A task is complete only when:

* acceptance criteria are met;
* required tests pass;
* required gate passes;
* review requirements are satisfied;
* unresolved risks are reported;
* ownership conflicts are absent;
* the final diff contains no unrelated work;
* required developer approvals are recorded.

Statements such as "should work", "looks correct", or "I am confident" are not completion evidence.
