# AI Engineering Workflow

This directory contains the source rules and task-local templates used by the repository's human-mediated multi-agent workflow.

## Source-of-truth hierarchy

1. `.ai/guidelines/**` contains durable repository rules.
2. Laravel Boost compiles those rules into generated `AGENTS.md` and `CLAUDE.md` instructions.
3. Superpowers skills provide execution methods such as brainstorming, planning, test-driven development, debugging, and verification.
4. `.ai/runs/<task-id>/` contains temporary task packets and handoffs.
5. Git state, tests, and quality gates provide objective completion evidence.
6. The developer retains final authority over material decisions, merge, and release.

Do not edit `AGENTS.md` or `CLAUDE.md` manually. Change the source guideline and regenerate the compiled instructions.

## Mandatory Serena use

Every AI provider, model, CLI, and role must initialize and use Serena MCP before internal repository tools. Internal tools are allowed only as declared fallback when the required Serena capability is unavailable or fails. See:

- `.ai/guidelines/00-serena-tool-mandate.md`
- `.ai/guidelines/01-serena.md`

## Handoff lifecycle

A normal Standard task follows this sequence:

1. Orchestrator creates a task packet.
2. Developer transfers the self-contained assignment to the writer CLI.
3. Writer returns completion evidence and a reviewer prompt.
4. Developer transfers the fixed diff to an independent reviewer CLI.
5. Reviewer returns structured findings and the next assignment.
6. Writer resolves accepted findings.
7. Integrator runs the finishing gate and produces the final report.

Temporary coordination belongs in `.ai/runs/`, not Serena memory and not generated instruction files.

## Templates

- `templates/task-packet.yaml`
- `templates/completion-evidence.yaml`
- `templates/next-agent-prompt.md`
- `templates/review-findings.yaml`
- `templates/finding-resolution.yaml`
- `templates/final-report.md`

Copy the required templates into `.ai/runs/<task-id>/` and fill them with exact Git state, authority, evidence, and stop conditions.

## Regeneration

After changing `.ai/guidelines/**`, run the repository's Laravel Boost instruction-generation command and review the generated `AGENTS.md` and `CLAUDE.md` diff. The generated files must reflect the source guidelines and must not contain task-local state.
