# Independent Review Rubric

This guideline defines evidence-based review decisions for Standard and Deep tasks.

## Decisions

Use exactly one decision:

- `APPROVE` — no material findings.
- `APPROVE_WITH_NON_BLOCKING_FINDINGS` — only concrete, non-blocking P3 findings remain.
- `REQUEST_CHANGES` — one or more blocking findings require correction.
- `BLOCKED` — the diff, requirements, environment, or evidence is insufficient for a valid review.

## Severity

- `P0` — security breach, privilege escalation, data loss, destructive operational failure, or secret exposure.
- `P1` — broken authorization, broken domain invariant, major regression, or serious data-integrity failure.
- `P2` — material edge case, missing material test, incorrect framework behavior, or concrete maintainability risk.
- `P3` — non-blocking improvement with a concrete impact.

Preferences without demonstrated impact are not findings.

## Required finding format

```text
Finding:
- ID: REV-001
- Severity: P0|P1|P2|P3
- Blocking: yes|no
- Category:
- Requirement:
- File/symbol:
- Evidence:
- Impact:
- Recommended resolution:
```

Every finding must cite repository evidence, an acceptance criterion, a failed check, or a verified framework rule. Speculation without evidence is not a finding.

## Review priorities

Review in this order:

1. acceptance-criteria compliance;
2. authorization, authentication, and sensitive-data exposure;
3. domain invariants and data integrity;
4. validation and failure behavior;
5. database transactions, concurrency, and idempotency;
6. Laravel, Inertia, Vue, and repository conventions;
7. test validity and missing material scenarios;
8. regression risk;
9. unnecessary complexity;
10. unrelated scope expansion.

## Independence

The final reviewer must not be the writer for Standard or Deep work. Reviewers remain read-only, use Serena MCP first, and may use internal tools only under the degraded-operation rules in `.ai/guidelines/00-serena-tool-mandate.md` and `.ai/guidelines/01-serena.md`.

## Resolution

Each finding keeps its stable ID through the fix and integration phases. The resolution must be `accepted`, `rejected` with evidence, or `deferred` with explicit human approval and residual risk. Blocking findings may not be silently omitted from later handoffs.