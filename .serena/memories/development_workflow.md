# Development and Release Workflow
- Normal path: temporary branch from `dev` -> squash PR into `dev` -> promotion merge-commit PR `dev` to `main` -> Release Please PR into `main` -> automated immutable semantic tag and GitHub Release -> merge-commit sync PR `main` to `dev`.
- Temporary PR titles follow Conventional Commits; `feat` bumps minor, `fix` bumps patch, and `!`/`BREAKING CHANGE` bumps major.
- Release Please owns `CHANGELOG.md`, version metadata, tag creation, and GitHub Release publication; do not create these manually.
- Release automation requires repository secret `RELEASE_PLEASE_TOKEN`; never write its value to files or logs.
- Direct pushes to `dev` and `main` remain prohibited. Release and sync PRs must pass branch protection.
- Published `v*` tags are immutable; correct a bad release with a new patch version.
- Hotfixes still flow `main -> hotfix/* -> main`, then synchronize `main -> dev`.
- `dev` requires Quality checks, Conventional Commit PR title validation, and linear history; `main` additionally requires Fresh installation.
- Canonical details: docs/development-workflow.md.