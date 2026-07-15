---
name: jalara-agentic-workflow
description: Orchestrate repository work across OpenAI, Anthropic, and Google agents using risk-based modes, one-writer ownership, deterministic gates, and cross-provider review.
disable-model-invocation: true
---

# Jalara Agentic Workflow

Use this skill as the provider-neutral entrypoint for repository changes.

## Required sources

Read before acting:

- `.agents/workflow.yaml`
- `.agents/policies/risk.yaml`
- `.agents/policies/routing.yaml`
- `.agents/policies/approvals.yaml`
- `.agents/policies/tools.yaml`

Use the contracts under `.agents/contracts/` for task packets, handoffs, and review findings.

## Process

1. Resolve the GitHub issue, pull request, or user request that is the system of record.
2. Inspect repository facts before asking questions.
3. Score risk and select `express`, `standard`, or `critical` mode.
4. Create a task packet and identify unresolved decisions.
5. Verify branch preconditions:
   - clean worktree;
   - branch is not `dev` or `main`;
   - branch descends from `dev`;
   - one writer owns the branch.
6. Activate only the domain skills required by the task.
7. Implement within the declared scope using tests at agreed seams.
8. Run targeted checks during implementation, then `composer agent:gate` before review.
9. Send the fixed-point diff to a reviewer from another provider. Reviewers are read-only.
10. Return findings to the writer. Allow at most two targeted fix cycles for an identical gate failure.
11. Publish evidence and the handoff in the GitHub issue or pull request.
12. Open a pull request to `dev`. Human approval remains mandatory where policy requires it.

## Mode rules

### Express

No formal grill or spec. Use for mechanical, local, reversible work. Still requires cross-provider review.

### Standard

Resolve no more than three material decisions before producing an implementation brief. Use targeted and full local gates.

### Critical

Require research, full grilling, explicit domain decisions, a spec, tracer-bullet tickets, human plan approval, adversarial review, and human merge approval.

## Ownership rules

- Only one agent may write to a branch at a time.
- Parallel agents may only research or review.
- The writer may not be the final reviewer.
- Reviewers must not modify source code.
- Do not expand scope to clean up unrelated code.

## Completion rule

A task is complete only when acceptance criteria, required gates, independent review, and required approvals have evidence. Confidence statements are not evidence.
