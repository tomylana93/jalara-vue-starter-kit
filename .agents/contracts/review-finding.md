# Independent Review Finding

Reviewers are read-only. Return findings to the current writer instead of modifying source code.

```yaml
finding:
  severity: P0|P1|P2|P3
  category: spec|standards|regression|security|data_integrity|maintainability
  file: ""
  line: null
  requirement: ""
  evidence: ""
  impact: ""
  recommended_fix: ""
```

## Severity

- `P0`: immediate security or data-loss risk; stop the workflow.
- `P1`: incorrect behavior, major regression, or unmet acceptance criterion; must fix before merge.
- `P2`: meaningful maintainability, edge-case, or standards problem; normally fix before merge.
- `P3`: optional improvement with no material correctness impact.

## Review axes

1. Specification and acceptance-criteria compliance.
2. Repository standards and established patterns.
3. Regression risks and edge cases.
4. Security and data integrity.

Every finding must cite concrete evidence. Preferences without impact are not findings.
