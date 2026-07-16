# AI Agent Workflow

This repository uses three AI providers as a coordinated engineering team:

* **OpenAI agent**: orchestration, architecture, planning, difficult debugging, CI, and release analysis.
* **Anthropic agent**: complex implementation, domain logic, security-sensitive work, and code review.
* **Google agent**: codebase scouting, frontend implementation, mechanical refactoring, and fast verification.

These assignments are defaults based on capability, not permanent rankings. The developer may override them for a specific task.

All agents share the same Serena MCP project. Serena provides shared code intelligence and durable project context, but it is **not a concurrency lock**. Multiple agents seeing the same symbols does not make simultaneous edits safe.

---

## 1. Core operating rules

Every task follows these rules:

1. One task has one **orchestrator**.
2. One working branch or worktree has one **writer** at a time.
3. Agents may research, inspect code, analyze tests, and review in parallel.
4. Two agents must not modify the same file, symbol, migration, route group, generated artifact, or dependency manifest concurrently.
5. The final reviewer must be different from the writer.
6. Completion requires evidence from tests and gates, not an agent's confidence statement.
7. Do not expand scope to unrelated cleanup.
8. Do not commit directly to `main` or `staging`.
9. Permanent changes enter `dev` through a pull request unless the developer explicitly directs otherwise.
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

Default provider:

* OpenAI for architecture, debugging, CI, release, or cross-cutting work;
* Anthropic for domain-heavy or security-sensitive work;
* Google for frontend-focused or mechanical work.

Only one orchestrator is active for a task.

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

Typical assignments:

* locate relevant symbols and call sites;
* identify existing patterns;
* inspect tests;
* inspect database structure;
* research framework documentation;
* analyze regression risks;
* propose implementation boundaries.

Scouts must not edit files or create competing implementations on the writer's branch.

### Reviewer

Reviewers are read-only.

They inspect:

* acceptance-criteria compliance;
* regressions;
* security and data integrity;
* existing conventions;
* test coverage;
* unnecessary complexity;
* scope expansion.

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

## 3. Shared Serena rules

Every agent must begin with:

1. `activate_project`
2. `initial_instructions`

No Bash, broad file read, grep, edit, or write operation may occur before these two calls complete.

### Shared Serena usage

Serena is used for:

* symbol discovery;
* reference tracing;
* implementation lookup;
* precise code navigation;
* architectural memories;
* durable repository conventions.

Serena must not be treated as:

* a file lock;
* a branch lock;
* a substitute for Git;
* a task queue;
* a guarantee that another agent is not editing the same code.

### Fixed-point rule

Before parallel work begins, the orchestrator records the Git commit SHA used as the fixed point.

Every participating agent must report:

```text
Fixed point: <commit-sha>
Role: orchestrator|writer|scout|reviewer
Assignment: <bounded task>
Write access: yes|no
Owned areas: <files, directories, or symbols>
```

An agent must stop if its checkout no longer matches the declared fixed point and the difference has not been coordinated.

### Serena memory ownership

Durable memories may contain:

* architecture decisions;
* domain invariants;
* project conventions not obvious from code;
* changed tooling or release procedures.

Memories must not contain:

* temporary task status;
* speculative findings;
* full code snippets;
* secrets;
* duplicated framework documentation;
* information easily rediscovered from code.

Only the orchestrator or an explicitly assigned agent may create or modify durable shared memories.

For task-local coordination, prefer the GitHub issue, task handoff, or agent response. Do not turn Serena memory into a shared chat room.

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

Examples:

* text or copy changes;
* isolated styling;
* simple component reuse;
* mechanical rename with known references;
* formatting or lint fixes;
* a small bug with an obvious cause.

Routine work may use:

* one writer;
* one parallel scout or reviewer when useful;
* targeted tests;
* standard finishing gate.

Routine tasks do not require the workflow to stop merely to ask for classification approval.

### Standard

Use when the task involves normal feature or bug work with moderate risk.

Examples:

* new CRUD following an existing pattern;
* additive migrations;
* frontend and backend changes in one feature;
* changes across several files;
* non-trivial validation;
* integration with an already installed package;
* refactoring behavior without changing architecture.

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

Agents may safely run these jobs concurrently:

* architecture analysis;
* existing-pattern discovery;
* documentation research;
* test-gap analysis;
* security review;
* frontend design analysis;
* database-schema inspection;
* CI or deployment analysis;
* read-only code review.

Example:

```text
OpenAI:
- orchestrate task;
- define architecture and acceptance criteria;
- inspect cross-module impact.

Anthropic:
- inspect domain invariants and security risks;
- challenge the proposed architecture;
- later implement the approved backend changes.

Google:
- inspect frontend patterns and affected components;
- identify reusable shadcn components;
- later review or implement a separate frontend slice.
```

### Default implementation model

The safest default is:

```text
OpenAI       -> orchestrator and architecture
Anthropic    -> primary writer
Google       -> read-only scout and final frontend/repository reviewer
```

The actual assignment must follow task characteristics rather than provider preference.

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

* the same file;
* the same class or Vue component;
* migrations;
* route files;
* `composer.json` or `composer.lock`;
* `package.json` or `pnpm-lock.yaml`;
* authentication configuration;
* authorization catalogs;
* generated Wayfinder files;
* shared types used by both frontend and backend;
* the same tests;
* the same Serena memory.

When ownership overlaps, convert all but one agent to read-only review mode.

---

## 6. Provider routing

### OpenAI agent

Primary strengths:

* orchestration;
* architecture;
* specification;
* difficult debugging;
* concurrency analysis;
* CI and release workflows;
* cross-cutting refactors.

Preferred roles:

* orchestrator;
* architecture challenger;
* debugging writer;
* release reviewer;
* final integrator.

### Anthropic agent

Primary strengths:

* complex implementation;
* domain reasoning;
* security-sensitive code;
* long-context repository changes;
* detailed review.

Preferred roles:

* backend writer;
* domain-model reviewer;
* security reviewer;
* architecture challenger.

### Google agent

Primary strengths:

* fast codebase scouting;
* frontend and UI work;
* mechanical refactoring;
* pattern discovery;
* broad read-only inspection.

Preferred roles:

* frontend writer;
* scout;
* mechanical-refactor writer;
* UI reviewer.

Google may perform Deep work only when the selected model has sufficient reasoning capability and the orchestrator explicitly assigns it. Provider names alone do not determine permission. Task risk and actual model capability do.

---

## 7. Task startup

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

---

## 8. Mandatory pre-flight evidence

Before modifying files, each writer posts:

```text
---
Pre-flight:
- Role: writer
- Provider:
- Classification:
- Fixed point:
- Branch/worktree:
- Owned files or symbols:
- Serena: activated and initial instructions read
- Memories: <names> or not needed
- Laravel Boost docs: used or not needed
- Context7: used or not needed
- Skills: <names>
- UI components checked: yes or no UI involved
- Acceptance criteria understood: yes
- Conflicting writer ownership: none
---
```

A scout or reviewer uses the same block but declares:

```text
Write access: no
```

---

## 9. Deep workflow

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

Each agent returns:

* repository evidence;
* affected symbols;
* risks;
* alternatives;
* recommendation;
* unresolved decisions.

### Phase 2: Design

The orchestrator combines findings into one design.

The design must identify:

* problem;
* current behavior;
* proposed behavior;
* data model impact;
* backend boundaries;
* frontend boundaries;
* authorization impact;
* failure cases;
* testing strategy;
* migration and rollback strategy;
* rejected alternatives.

Conflicts between providers must be presented explicitly, not averaged into vague prose.

### Phase 3: Grill

Use `/grill-me` to test:

* hidden assumptions;
* edge cases;
* data integrity;
* security boundaries;
* concurrency;
* rollback;
* operational failure;
* maintenance cost.

Material unresolved decisions require developer approval.

### Phase 4: Implementation assignment

The orchestrator defines:

* one primary writer;
* optional isolated parallel writers;
* exact file and symbol ownership;
* integration order;
* targeted tests;
* review assignments.

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

## 10. Routine and Standard workflows

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

A separate reviewer is optional for truly mechanical work.

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

## 11. Finishing gate

Run in this order:

```text
Finishing gate:
[ ] vendor/bin/pint --dirty --format agent
[ ] pnpm run lint
[ ] pnpm run format
[ ] php artisan wayfinder:generate --with-form --no-interaction
    when routes, controllers, or invokable actions changed
[ ] targeted tests passed
[ ] composer ci:check passed
[ ] reviewer findings resolved or explicitly accepted
[ ] no unrelated files changed
[ ] Serena memory updated or not needed with reason
[ ] final diff reviewed against fixed point
[ ] commit or pull request explicitly requested
```

Run Wayfinder generation when any of these change:

* `routes/*.php`;
* controllers;
* invokable actions;
* route names or parameters.

Generated files have one owner: the integrator or designated backend writer.

### Gate failure

Safe automatic fixes:

* Pint;
* ESLint fix;
* Prettier.

For logic, PHPStan, type, or test failures:

1. use `systematic-debugging`;
2. identify root cause;
3. make a targeted fix;
4. rerun the smallest failing check;
5. rerun the full gate.

Stop after two or three unsuccessful cycles when:

* the same failure remains;
* the required fix changes architecture;
* scope would expand;
* another writer owns the affected area.

Do not loop blindly.

---

## 12. Handoff format

Every writer returns:

```text
Handoff:
- Task:
- Provider:
- Role:
- Fixed point:
- Branch/worktree:
- Scope completed:
- Files changed:
- Tests added or updated:
- Commands run:
- Checks passed:
- Decisions made:
- Known risks:
- Unresolved issues:
- Serena memories changed:
- Recommended next owner:
```

Every receiving agent verifies the fixed point and current diff before continuing.

---

## 13. Git and branch rules

Normal development flow:

```text
dev
  -> task branch or provider worktree branch
  -> integration branch when multiple writers are used
  -> pull request to dev
  -> dev to staging
  -> staging to main
```

Rules:

* never let two writers share one working tree;
* never use `dev`, `staging`, or `main` as an experimental writer branch;
* parallel writers branch from the same fixed point;
* the integrator owns conflict resolution;
* do not hide conflicts by accepting both implementations;
* long-lived branch promotion uses merge commits;
* direct pushes to `main` are forbidden;
* releases remain developer-controlled.

---

## 14. Completion definition

A task is complete only when:

* acceptance criteria are met;
* required tests pass;
* required gate passes;
* review requirements are satisfied;
* unresolved risks are reported;
* ownership conflicts are absent;
* the final diff contains no unrelated work;
* required developer approvals are recorded.

Statements such as “should work”, “looks correct”, or “I am confident” are not completion evidence.
