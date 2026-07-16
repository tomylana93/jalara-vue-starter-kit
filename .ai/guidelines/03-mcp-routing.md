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
