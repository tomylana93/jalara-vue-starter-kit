<laravel-boost-guidelines>
=== .ai/01-serena rules ===

# Serena MCP

The Serena MCP server provides semantic code navigation, reference analysis, precise symbol editing, and durable project instructions shared across AI agents.

All participating AI providers connect to the same Serena project. This provides shared repository intelligence and durable context, but it does **not** provide file locking, symbol locking, branch locking, task ownership, or concurrency control.

## Session Start — Mandatory First Actions

Before any Bash, Read, grep, repository search, file operation, or code modification, call these two Serena tools in this exact order:

1. `activate_project` — activate the current repository in Serena.
2. `initial_instructions` — read the project instruction manual.

Only these two calls are allowed before initialization completes.

Starting repository work without completing both calls is a workflow violation, even when the requested change appears trivial.

After initialization, confirm:

```text
Serena:
- Project activated: yes
- Initial instructions read: yes
- Role: orchestrator|writer|scout|reviewer
- Write access: yes|no
- Fixed point: <commit-sha>
- Owned areas: <files, directories, or symbols>
```

If the task does not yet have an assigned role, fixed point, or ownership boundary, remain read-only until the orchestrator defines them.

## Shared Serena Is Not a Concurrency Lock

Multiple agents may use the same Serena MCP project concurrently for read-only work.

Safe parallel Serena activities include:

* exploring symbols;
* tracing references;
* locating implementations;
* inspecting existing patterns;
* analyzing tests;
* reviewing code;
* researching architecture;
* reading project memories.

Sharing Serena does not make concurrent writes safe.

Serena does not prevent two agents from:

* editing the same file;
* replacing the same symbol;
* creating conflicting migrations;
* changing the same route;
* modifying shared configuration;
* overwriting generated files;
* updating the same memory with incompatible information.

Therefore:

1. Only one writer may modify a branch or worktree at a time.
2. Parallel writers must use separate Git branches and separate worktrees.
3. Parallel writers must have explicit, non-overlapping file or symbol ownership.
4. Agents without assigned write ownership must remain read-only.
5. When ownership overlaps or is unclear, stop and return control to the orchestrator.
6. Serena must never be treated as a replacement for Git branches, worktrees, diffs, or integration review.

## Fixed-Point Discipline

Before parallel agents begin, the orchestrator must declare the Git commit SHA used as the shared fixed point.

Every agent must report:

```text
Fixed point: <commit-sha>
Current branch or worktree: <name>
Role: orchestrator|writer|scout|reviewer
Write access: yes|no
Owned areas: <files, directories, or symbols>
```

An agent must stop before writing when:

* its checkout does not match the declared fixed point;
* another agent has changed an owned dependency;
* its assigned files overlap another writer's ownership;
* integration has advanced beyond its fixed point;
* the repository contains unrelated uncommitted changes.

Read-only scouts and reviewers may continue after the fixed point changes only when the orchestrator explicitly asks them to review the newer revision.

## Code Navigation — Serena First

Use Serena's semantic tools for code exploration and refactoring whenever a symbolic operation covers the need.

Do not use `grep`, `Bash cat`, or broad full-file reads as the default method for understanding code.

| Need                                                | Use                                                  |
| --------------------------------------------------- | ---------------------------------------------------- |
| Understand a class or file structure                | `get_symbols_overview` or `find_symbol` with `depth` |
| Find all callers of a function                      | `find_referencing_symbols`                           |
| Find a specific symbol                              | `find_symbol`                                        |
| Find implementations of an interface or abstraction | `find_implementations`                               |
| Search for a code or text pattern                   | `search_for_pattern`                                 |
| Replace a method or symbol body                     | `replace_symbol_body`                                |
| Insert code near a symbol                           | `insert_before_symbol` or `insert_after_symbol`      |
| Rename a symbol safely                              | `rename_symbol`, followed by reference verification  |

### Concrete examples

```text
❌ grep -rn "AppearanceTabs" resources/js
✅ mcp__serena__find_referencing_symbols("AppearanceTabs")

❌ cat -n FrontendLocaleExporter.php
✅ mcp__serena__find_symbol("FrontendLocaleExporter", depth=2)

❌ grep -rEln "lang:export|LangExport" app
✅ mcp__serena__search_for_pattern("lang:export", path="app")

❌ Read an entire large file to locate one method
✅ mcp__serena__find_symbol("methodName", include_body=true)

❌ grep for an interface name to guess its implementations
✅ mcp__serena__find_implementations("SomeInterface")
```

## Read-Only and Write Operations

### Read-only agents

Orchestrators, scouts, and reviewers may:

* inspect symbols;
* trace references;
* search patterns;
* inspect memories;
* analyze architecture;
* report findings.

They must not call write-capable Serena operations unless the orchestrator explicitly reassigns them as the writer.

### Writers

A writer may use precise Serena editing only inside its declared ownership boundary.

Before every edit, verify:

* the symbol belongs to the writer;
* no other writer owns the file;
* the current branch or worktree is correct;
* the current code still matches the expected fixed point;
* the edit does not silently expand the assigned scope.

A writer must stop when an edit requires changing files owned by another agent. Return the dependency to the orchestrator instead of crossing the boundary because it appears convenient.

## Full-File Read Exceptions

A complete file read is acceptable when:

* the file is approximately 100 lines or fewer;
* the file is non-code, such as configuration, documentation, or `.env.example`;
* the complete file is required to understand an inseparable flow;
* the file is a test whose full scenario must be inspected;
* Serena cannot parse the language or file type adequately.

Even under these exceptions:

* do not repeatedly read a file already read in full during the same session;
* do not read multiple large files when symbol-level inspection is sufficient;
* do not rewrite an entire file when a precise symbol edit is safer;
* do not use full-file replacement to bypass ownership boundaries.

## Serena Memory Rules

Serena memories are durable project knowledge, not temporary task coordination.

Memories may contain:

* architectural decisions and their rationale;
* domain invariants not obvious from source code;
* repository conventions;
* branching, release, or deployment procedures;
* durable tooling decisions;
* recurring operational constraints.

Memories must not contain:

* temporary task progress;
* speculative ideas;
* unresolved review findings;
* full code snippets;
* generic framework documentation;
* information easily rediscovered from source code;
* secrets, tokens, credentials, or personal data;
* parallel-agent ownership status that becomes stale after the task.

Only the orchestrator or an explicitly assigned memory owner may create, update, rename, or delete shared memories.

Before changing a memory:

1. read the existing memory;
2. confirm the decision is durable;
3. avoid duplicating another memory;
4. write the rationale, not merely the implementation;
5. report the memory name in the task handoff.

Do not use Serena memory as a chat channel between parallel agents. Use the task packet, GitHub issue, pull request, or structured handoff for task-local coordination.

## Refactoring Safety

Before a symbolic refactor:

1. locate the target symbol;
2. inspect its references;
3. inspect relevant tests;
4. verify ownership;
5. make the smallest precise edit;
6. inspect references again;
7. run the smallest relevant test;
8. report affected symbols to the integrator.

For cross-cutting renames or interface changes, one writer must own the complete refactor unless the orchestrator has explicitly partitioned non-overlapping callers.

Generated files, route helpers, dependency manifests, migrations, and shared type definitions must have a single designated owner.

## Evidence Requirement

Each agent must include Serena usage in its pre-flight evidence:

```text
Serena:
- Activated: yes
- Initial instructions read: yes
- Role:
- Write access:
- Fixed point:
- Owned areas:
- Memories read:
- Symbol tools used:
- Ownership conflict: none|blocked
```

At handoff, a writer must report:

```text
Serena handoff:
- Symbols inspected:
- Symbols modified:
- References verified:
- Memories changed:
- Ownership boundary respected: yes
- Fixed point or final commit:
```

Confidence is not evidence. Claims such as “the change should be safe” must be supported by reference inspection, tests, or both.

## Fallback — Serena MCP Unavailable

If Serena is unavailable:

1. state the failure explicitly in the pre-flight evidence;
2. remain read-only until role and ownership are confirmed;
3. use targeted repository search or ripgrep as the fallback;
4. read only the files and sections required;
5. use Git diff and reference searches to compensate for missing symbolic analysis;
6. do not perform broad or cross-cutting refactors without Serena unless the developer explicitly approves the degraded workflow.

Fallback example:

```text
Serena: unavailable
Fallback:
- Navigation: targeted ripgrep and focused file reads
- Write ownership: <declared areas>
- Refactor limitation: no cross-cutting symbolic rename
- Risk accepted by: <developer or orchestrator>
```

Do not silently fall back and continue as though Serena had been used.

## Stop Conditions

Stop and report to the orchestrator when:

* Serena initialization fails;
* the declared fixed point does not match the checkout;
* another writer owns the required file or symbol;
* the edit crosses an ownership boundary;
* two memories contain conflicting project decisions;
* repository state changed during analysis;
* the required refactor cannot be performed safely with available tools;
* the requested task depends on unresolved work from another agent;
* an agent appears to be modifying the same area concurrently.

The orchestrator must resolve the conflict, reassign ownership, or establish a new fixed point before work continues.

=== .ai/02-ai-workflow rules ===

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

=== .ai/03-mcp-routing rules ===

# MCP Tool Routing

This file defines which MCP or repository tool to use for each category of work.

These rules apply to all AI providers and layer on top of:

* the generated Laravel Boost guidelines;
* `.ai/guidelines/01-serena.md`;
* `.ai/guidelines/02-ai-workflow.md`;
* active domain skills.

This file governs tool selection and tool ownership. It does not replace task classification, branch ownership, fixed-point discipline, or review requirements.

## Core Routing Principles

1. Use the most repository-aware and version-aware tool available.
2. Inspect existing repository code before researching a generic solution.
3. Use read-only tools freely within the assigned scope.
4. Use write-capable tools only when the agent owns the affected files or symbols.
5. Do not duplicate another agent's investigation unless independently validating a risky conclusion.
6. Record reusable tool findings in the task handoff, not in temporary Serena memories.
7. Do not guess an external API when current documentation is available.
8. A tool being available does not mean every agent should call it.

## Agent Roles and Tool Permissions

### Orchestrator

The orchestrator may:

* assign documentation research;
* assign codebase discovery;
* inspect repository-wide impact;
* coordinate database and browser investigation;
* collect findings from other agents;
* designate the owner of write-capable tools.

The orchestrator remains read-only unless also explicitly assigned as the writer or integrator.

### Writer

The writer may:

* use Serena write operations within its declared ownership;
* generate files required by its implementation;
* install approved shadcn components;
* run formatting and autofix commands;
* run targeted tests and gates.

The writer must not modify files outside its ownership merely because an MCP tool makes the operation convenient.

### Scout

A scout may:

* search documentation;
* inspect symbols and references;
* inspect database schema;
* perform approved read-only database queries;
* inspect existing UI components;
* inspect recent browser logs;
* report findings.

A scout must not:

* install components;
* modify source code;
* run destructive commands;
* update generated files;
* change dependencies;
* modify shared Serena memories unless explicitly assigned.

### Reviewer

A reviewer uses read-only tools.

A reviewer may inspect:

* the fixed diff;
* affected symbols and references;
* relevant documentation;
* database assumptions;
* UI conventions;
* recent runtime errors;
* test coverage.

A reviewer returns evidence-backed findings to the writer. The reviewer does not silently fix findings.

## Mandatory Pre-Flight Evidence

Before the first file or code modification, the writer must show:

```text
---
Pre-flight:
- Role:
- Provider:
- Classification:
- Fixed point:
- Branch/worktree:
- Write ownership:
- Serena:
  - project activated
  - initial instructions read
- Laravel Boost:
  - tools required:
  - documentation queries:
- Context7:
  - libraries required:
  - or not needed:
- shadcn:
  - existing components inspected:
  - registry access required:
  - registry install owner:
- Runtime inspection:
  - database schema:
  - database query:
  - browser logs:
- Required skills:
- Tool ownership conflicts: none
---
```

Scouts and reviewers use the same structure but must state:

```text
Write access: no
```

The pre-flight block must reflect actual tool use. Do not mark a tool as used before calling it.

## Documentation Routing

### Repository Inspection Comes First

Before searching external documentation:

1. inspect the relevant existing symbols;
2. inspect sibling implementations;
3. inspect existing tests;
4. identify the exact API or behavior that requires verification.

Do not begin with broad framework research when the repository already contains the established pattern.

### Laravel Ecosystem and Installed Packages

Use Laravel Boost `search-docs` first for installed Laravel ecosystem packages, including:

* Laravel;
* Inertia;
* Vue integrations included in Boost coverage;
* Fortify;
* Pest;
* Wayfinder;
* Tailwind integrations;
* Laravel Boost;
* supported installed Spatie packages;
* other packages indexed by the active Boost installation.

Use broad topic-based queries rather than embedding package names unnecessarily.

Preferred:

```text
authorization middleware
deferred props testing
temporary upload validation
route generation form actions
```

Avoid:

```text
Laravel 13 authorization middleware documentation
Inertia v3 deferred props documentation
```

Boost already knows the installed package versions.

### External Libraries and Insufficient Boost Results

Use Context7 when:

* the library is outside Boost coverage;
* Boost returns no relevant result;
* Boost documentation is incomplete for the required API;
* the task uses a non-Laravel JavaScript or infrastructure library.

Context7 workflow:

1. resolve the library identifier;
2. query the exact topic;
3. record the library version or documentation context used;
4. report any mismatch with the repository's installed version.

Do not call Boost and Context7 for the same question by default. Use Context7 as validation only when:

* the change is Deep;
* documentation appears contradictory;
* the API is security-sensitive;
* Boost results are incomplete;
* the orchestrator explicitly requests independent verification.

### Official Documentation Fallback

Use official documentation only when both Boost and Context7 are unavailable or insufficient.

When falling back:

* use the package's official documentation;
* verify the documentation applies to the installed major version;
* record the source in the handoff;
* do not rely on blog posts or generated snippets when primary documentation exists.

Never guess an unfamiliar API from model memory.

## Parallel Documentation Research

Parallel research is allowed when agents investigate different questions.

Safe example:

```text
Agent A:
- Fortify authentication pipeline.

Agent B:
- Spatie permission behavior.

Agent C:
- Inertia form and frontend validation behavior.
```

Wasteful example:

```text
Agent A:
- Search password validation documentation.

Agent B:
- Search the same password validation documentation.

Agent C:
- Search the same password validation documentation again.
```

The orchestrator must assign distinct documentation questions.

Each research agent returns:

```text
Documentation finding:
- Question:
- Tool used:
- Package or library:
- Applicable version:
- Relevant behavior:
- Repository impact:
- Uncertainty:
```

Other agents should reuse this result unless independent verification is required.

## Code Navigation and Refactoring

Detailed Serena rules live in `.ai/guidelines/01-serena.md`.

For routing purposes:

| Need                 | Primary tool                                       | Fallback                                      |
| -------------------- | -------------------------------------------------- | --------------------------------------------- |
| Symbol overview      | Serena `get_symbols_overview`                      | focused file read                             |
| Find symbol          | Serena `find_symbol`                               | targeted repository search                    |
| Find callers         | Serena `find_referencing_symbols`                  | targeted ripgrep                              |
| Find implementations | Serena `find_implementations`                      | targeted repository search                    |
| Pattern search       | Serena `search_for_pattern`                        | ripgrep                                       |
| Precise symbol edit  | Serena symbolic editing                            | focused manual edit                           |
| Cross-cutting rename | Serena `rename_symbol` plus reference verification | stop or obtain approval for degraded workflow |

Only the designated writer may use Serena write operations.

Scouts and reviewers remain read-only even though Serena exposes editing tools to their sessions.

A shared Serena server provides shared repository intelligence. It does not grant shared write ownership.

## Database Routing

### Inspect Database Structure

Before changing:

* migrations;
* models;
* casts;
* relationships;
* indexes;
* constraints;
* query assumptions;

use Laravel Boost `database-schema` when available.

Do not infer the current schema solely from migration filenames. Existing environments may contain package tables, renamed columns, or schema details not obvious from a quick file scan.

The assigned database scout or writer reports:

```text
Database schema finding:
- Tables inspected:
- Columns and types:
- Indexes:
- Foreign keys:
- Constraints:
- Repository migrations compared:
- Relevant mismatch:
```

### Read-Only Database Inspection

Use Laravel Boost `database-query` for approved read-only inspection.

Permitted:

* `SELECT`;
* counts;
* aggregate inspection;
* checking representative records;
* confirming data shapes;
* investigating a reported state.

Not permitted during discovery:

* `INSERT`;
* `UPDATE`;
* `DELETE`;
* schema changes;
* unbounded data exports;
* queries containing secrets or unnecessary personal data.

Database writes must be implemented through:

* migrations;
* application code;
* factories;
* seeders;
* tests;
* explicitly approved operational commands.

Do not use database-query as a shortcut around application behavior.

### Parallel Database Work

Only one agent should own a database investigation question at a time.

Multiple read-only database agents are permitted only when:

* their queries target distinct concerns;
* query load is reasonable;
* they do not expose sensitive data;
* the orchestrator explicitly assigned both investigations.

One writer must own schema changes for a task. Do not split related migrations across concurrent writers.

## Runtime and Browser Investigation

Use Laravel Boost `browser-logs` for recent frontend errors, exceptions, and browser console output.

Rules:

1. inspect only recent logs relevant to the reproduced issue;
2. ignore stale unrelated entries;
3. correlate logs with the current fixed point;
4. report timestamps and affected pages where available;
5. do not treat a console warning as the root cause without code evidence;
6. do not assign multiple agents to inspect the same log stream unless independently validating a difficult issue.

Use application logs or framework logs as fallback when browser logs are unavailable.

Runtime findings must distinguish:

* observed evidence;
* likely cause;
* confirmed root cause.

Example:

```text
Runtime finding:
- Observed:
- Reproduction:
- Log source:
- Relevant entry:
- Suspected cause:
- Confirmed by:
```

## URL Resolution

Use Laravel Boost `get-absolute-url` before sharing a local or application URL with the developer.

Do not guess:

* scheme;
* hostname;
* application port;
* subdirectory;
* local development domain.

A URL generated from route knowledge is not necessarily the URL served by the current environment.

## UI Component Routing

### Step 1: Reuse Existing Components

Before creating or installing a component:

1. inspect `@/components/ui`;
2. inspect domain-specific shared components;
3. inspect sibling pages;
4. inspect existing design and accessibility conventions.

Do not install a registry component merely because the agent recognizes its name.

### Step 2: Use shadcn MCP

When a suitable component does not exist, use the shadcn MCP to:

* search the configured registry;
* inspect component availability;
* inspect dependencies;
* install an approved component.

Only the designated UI writer or integrator may run a shadcn install operation because installation changes repository files.

Scouts may search the registry but must not install components.

Before installation, report:

```text
shadcn proposal:
- Required primitive:
- Existing component unavailable:
- Registry component:
- Files expected to change:
- Dependencies introduced:
- Installation owner:
```

A new dependency still requires developer approval when repository policy requires it.

### Step 3: Custom UI

When no registry component fits:

* use Vue;
* use existing `reka-ui` primitives;
* use Tailwind CSS;
* activate `tailwindcss-development`;
* follow existing accessibility patterns.

Do not introduce another UI framework or widget library without explicit approval.

### UI Ownership Rules

Parallel UI writers must not modify:

* the same component;
* the same barrel export;
* the same shared type;
* the same form schema;
* the same page;
* the same registry-generated component;
* `package.json`;
* `pnpm-lock.yaml`.

Shared UI primitives and registry installations have one owner per task.

## Generated Files and Tool Ownership

Generated artifacts must have a single owner.

This includes:

* Wayfinder output;
* shadcn-generated files;
* localization exports;
* API clients;
* schema-generated types;
* dependency lockfiles;
* build manifests committed to the repository.

The orchestrator must assign the owner before parallel implementation begins.

Other writers must not regenerate or manually modify the same artifacts.

When routes, controllers, or invokable actions change, the designated owner runs:

```bash
php artisan wayfinder:generate --with-form --no-interaction
```

Run generation after relevant source changes are integrated, not independently in multiple worktrees unless each result is intentionally isolated and only one result will be integrated.

## Tool Result Handoff

Every agent must return useful findings rather than merely listing tool names.

Required format:

```text
Tool handoff:
- Tool:
- Purpose:
- Scope inspected:
- Result:
- Evidence:
- Repository implication:
- Follow-up required:
```

Do not include secrets, credentials, tokens, or unnecessary personal data.

Do not copy large documentation passages into handoffs. Summarize the relevant behavior and cite the source or query used.

## Write-Capable Tool Policy

The following operations are write-capable and require ownership:

* Serena symbolic edits;
* shadcn component installation;
* file-editing tools;
* formatting with write mode;
* ESLint autofix;
* Prettier write;
* Pint formatting;
* Rector process without dry-run;
* code generation;
* migrations;
* dependency installation;
* Git commit, branch, push, or pull request actions.

Read-only access to a tool does not imply permission to use its write operations.

Before running a write-capable operation, verify:

* assigned role is writer or integrator;
* branch or worktree is correct;
* owned files are explicit;
* no parallel writer owns affected files;
* developer approval exists where required.

## Autofix Routing

Safe automatic fixes:

* `vendor/bin/pint --dirty --format agent`;
* `pnpm run lint`;
* `pnpm run format`.

Run these only after implementation changes are integrated into the writer's branch or integration branch.

Do not let multiple parallel agents run autofix against overlapping files.

Guarded transformations:

* Rector write mode;
* mass renames;
* codemods;
* generated type replacement;
* dependency updates.

Guarded transformations require:

1. explicit ownership;
2. clean diff before execution;
3. diff inspection afterward;
4. targeted tests;
5. rollback capability.

## Degraded Operation

### Laravel Boost Unavailable

Use:

1. Context7 for documentation;
2. migration files and model definitions for schema inspection;
3. framework and application logs for runtime investigation;
4. official documentation as final fallback.

State what precision is lost.

### Context7 Unavailable

Use:

1. Laravel Boost when the library is covered;
2. official library documentation;
3. repository source and installed type definitions.

Do not guess.

### Serena Unavailable

Follow the fallback policy in `.ai/guidelines/01-serena.md`.

Do not perform a cross-cutting symbolic refactor without explicit approval.

### shadcn MCP Unavailable

1. inspect existing UI components;
2. check whether the component is already installed;
3. build with existing Vue, reka-ui, and Tailwind patterns;
4. do not add an alternative UI library without approval.

### Required Tool Unavailable

A missing optional tool does not automatically block the task.

Stop only when:

* the missing tool is necessary for safe implementation;
* no documented fallback exists;
* fallback materially changes an approved design;
* the task involves high-risk behavior that cannot be verified safely.

Record the fallback in pre-flight and handoff evidence.

## Stop Conditions

Stop and return control to the orchestrator when:

* a write-capable tool affects files outside ownership;
* two agents are investigating or modifying the same area without coordination;
* documentation sources materially disagree;
* installed package versions cannot be determined;
* database inspection reveals a schema mismatch;
* shadcn installation introduces an unapproved dependency;
* a generated file has multiple owners;
* runtime evidence comes from a different fixed point;
* the required MCP is unavailable and no safe fallback exists;
* tool output contains unexpected secrets or sensitive data.

## Quick Reference

| Need                            | Primary tool                     | Fallback                        | Default owner              |
| ------------------------------- | -------------------------------- | ------------------------------- | -------------------------- |
| Laravel ecosystem documentation | Laravel Boost `search-docs`      | Context7, then official docs    | assigned researcher        |
| External library documentation  | Context7                         | official docs                   | assigned researcher        |
| Code navigation                 | Serena symbolic tools            | targeted repository search      | any read-only role         |
| Code modification               | Serena symbolic editing          | focused manual edit             | writer only                |
| Database structure              | Laravel Boost `database-schema`  | migrations and model inspection | database scout or writer   |
| Read-only database inspection   | Laravel Boost `database-query`   | approved application command    | assigned scout             |
| Browser errors                  | Laravel Boost `browser-logs`     | application logs                | debugging scout or writer  |
| Application URL                 | Laravel Boost `get-absolute-url` | environment inspection          | responding agent           |
| Existing UI component           | repository component inspection  | none                            | UI scout or writer         |
| New UI primitive                | shadcn MCP                       | Vue plus reka-ui and Tailwind   | UI writer                  |
| Generated route helpers         | Wayfinder command                | none                            | designated generator owner |
| Formatting and safe autofix     | Pint, ESLint, Prettier           | manual correction               | writer or integrator       |

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2
- @inertiajs/vue3 (INERTIA_VUE) - v3
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v10
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
