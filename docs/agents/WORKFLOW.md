# Agentic Development Workflow

This repository uses GitHub as the system of record for work performed across OpenAI, Anthropic, and Google agents.

## Operating model

- One task has one orchestrator.
- One branch has one writer at a time.
- Parallel agents are read-only researchers or reviewers.
- Standard and critical work require a reviewer from another provider.
- Completion requires evidence from tests, gates, review, and approvals.

The machine-readable source is `.agents/workflow.yaml`. Policies and contracts live under `.agents/policies/` and `.agents/contracts/`.

## Workflow modes

### Express

Use for local, mechanical, reversible changes. Skip formal grilling and specifications. Run targeted checks, the canonical gate, and cross-provider review.

### Standard

Use for normal features and bugs. Discover repository context, resolve up to three material decisions, create an implementation brief, implement, run local gates, and obtain independent review.

### Critical

Use for security, authentication, authorization models, destructive migrations, concurrency, public contracts, architecture, and release workflows. Require research, full grilling, explicit decisions, a specification, tracer-bullet tickets, human plan approval, adversarial review, and human merge approval.

## Branch flow

All work starts from `dev` on a dedicated branch and returns through a pull request:

```text
feature/fix branch -> dev -> release PR -> main
```

Writers must verify a clean worktree, confirm the branch is not `dev` or `main`, and verify it descends from `dev`.

## Canonical validation

Run:

```bash
composer agent:gate
```

This executes backend and frontend quality checks, tests, and the production frontend build. During implementation, run the smallest relevant test or typecheck first.

Safe automatic fixes are limited to Pint, ESLint fix mode, and Prettier. Rector is a guarded transformation and requires diff review.

## Provider routing

Routing is an initial policy, not a permanent ranking:

- OpenAI: orchestration, architecture, debugging, CI, and release work.
- Anthropic: complex implementation, security review, and domain reasoning.
- Google: frontend, mechanical refactoring, and codebase scouting.

Use task outcomes to revise routing over time. The writer must never be the final reviewer.

## Handoffs and review

Use the contracts in `.agents/contracts/` and publish completed task packets and handoffs to the relevant GitHub issue or pull request. Reviewers remain read-only and return evidence-backed findings to the writer.

Never place credentials, tokens, secrets, or personal data in workflow artifacts.
