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
