# Development and Release Workflow
- Normal path: temporary `feature/*` branch from `dev` -> pull request to `dev` -> squash merge -> promotion pull request `dev` to `main` -> merge commit -> annotated semantic-version tag on `main` -> manual SSH deployment.
- Direct pushes to permanent branches `dev` and `main` are prohibited; all changes arrive through pull requests.
- Temporary branch prefixes: `feature/*`, `fix/*`, `refactor/*`, `chore/*`, `docs/*`.
- Promotion PRs contain no release-only edits; required edits must first enter `dev`.
- Hotfix path: branch `hotfix/*` from `main`, squash PR into `main`, then merge-commit PR from `main` back to `dev`.
- Published `v*` tags are immutable; correct a bad release with a new patch version.
- `dev` requires Quality checks and linear history; `main` additionally requires Fresh installation.
- Canonical details: docs/development-workflow.md.