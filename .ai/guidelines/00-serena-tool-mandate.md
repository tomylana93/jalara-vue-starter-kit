# Serena Tool Mandate

This rule applies to every AI provider, model, CLI, role, and task classification.

## Mandatory precedence

1. Serena MCP is the primary repository inspection, navigation, reference-analysis, and symbol-editing tool.
2. Every agent must initialize Serena using the sequence defined in `.ai/guidelines/01-serena.md` before any repository work.
3. After initialization, use the applicable Serena operation before using an internal file-read, search, grep, or editing tool.
4. Internal tools are fallback tools, not parallel defaults and not convenience substitutes.
5. A provider or CLI having stronger built-in repository tools does not waive this requirement.

## Allowed fallback

Fallback to internal tools is allowed only when the required Serena capability is unavailable, fails, cannot parse the relevant file type, or cannot perform the required operation safely.

Before fallback, the agent must report:

```text
Serena fallback:
- Required capability:
- Serena operation attempted:
- Failure or limitation:
- Internal fallback tool:
- Fallback scope:
- Safety limitation:
```

The fallback must be targeted to the smallest necessary scope. Broad repository reads, broad grep, full-file replacement, or cross-cutting refactors remain prohibited unless the developer explicitly approves degraded operation.

## Evidence

The canonical pre-flight and every handoff must state one of:

- `Serena: used` with the relevant operations;
- `Serena: unavailable` with the fallback evidence block above.

An agent may not claim Serena was unavailable merely because an internal tool was faster or more familiar.

## Stop conditions

Stop and return control to the orchestrator when:

- Serena initialization fails and the task requires writing;
- a safe fallback cannot preserve reference analysis or ownership boundaries;
- a cross-cutting refactor would rely only on textual search;
- the fallback would require broad reads or edits outside assigned ownership;
- the agent cannot prove which repository state was inspected.

Read-only work may continue under degraded operation only within the declared fallback scope. Write work requires explicit role and ownership confirmation, and risky or cross-cutting writes require developer approval.