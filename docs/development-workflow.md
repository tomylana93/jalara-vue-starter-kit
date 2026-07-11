# Development and Release Workflow

Jalara uses a lightweight pull-request workflow designed for a solo developer. The process keeps every permanent branch protected without adding a fictional approval committee consisting of the same person wearing different hats.

## Branches

### Permanent branches

- `dev` is the integration branch for daily development.
- `main` is the production and release branch.

Direct pushes to both branches should be blocked. Every change must arrive through a pull request.

### Temporary branches

Create temporary branches from `dev`:

```text
feature/*
fix/*
refactor/*
chore/*
docs/*
```

Examples:

```text
feature/modular-foundation
fix/login-validation
refactor/auth-actions
chore/update-dependencies
```

## Normal flow

```text
feature/* -> dev -> main -> Release Please PR -> version tag -> GitHub Release
                                  |
                                  v
                             main -> dev
```

1. Create a temporary branch from `dev`.
2. Open a pull request into `dev` with a Conventional Commit title.
3. Merge with squash merge after the required CI check passes.
4. When `dev` is ready for release, open a promotion pull request from `dev` into `main`.
5. Merge the promotion pull request with a merge commit.
6. Merge the Release Please PR after reviewing its generated changelog and version.
7. Release Please creates the immutable semantic version tag and publishes the GitHub Release.
8. Merge the automated synchronization pull request from `main` back into `dev` with a merge commit.
9. Deploy the tag or `main` manually through SSH.

Temporary pull requests use Conventional Commit titles. Release Please reads the squash commit produced from those titles: `feat` bumps minor, `fix` bumps patch, and `!` or a `BREAKING CHANGE` footer bumps major.

After a promotion pull request is merged, Release Please opens or updates a release pull request against `main`. Merging the release pull request updates `CHANGELOG.md`, creates the tag, and publishes the GitHub Release. The published-release workflow then opens a `main` to `dev` synchronization pull request.

Promotion pull requests must not contain release-only edits. Release Please owns the release-only changelog and version metadata changes on `main`, then synchronizes them back into `dev`.

## Hotfix flow

Production hotfixes branch from `main`:

```text
main -> hotfix/* -> main
```

After the hotfix is merged, open a second pull request:

```text
main -> dev
```

This prevents a future `dev` promotion from reintroducing the corrected defect.

## Merge policy

- Temporary branch into `dev`: squash merge.
- `dev` into `main`: merge commit.
- Hotfix into `main`: squash merge.
- `main` back into `dev`: merge commit.

## Release tags

Release Please creates semantic version tags and GitHub Releases when its release pull request is merged. Do not create routine release tags manually.

Published tags must never be moved or deleted. Create a new patch version when a release is incorrect.

Repository administrators must configure `RELEASE_PLEASE_TOKEN` with contents and pull-request write access. Never store the token in the repository.

## Recommended GitHub rulesets

Apply the following rules to `dev` and `main`:

- require a pull request before merging;
- require the `Quality checks` status check;
- require the `Validate PR title` status check on temporary pull requests into `dev`;
- require conversation resolution;
- block force pushes;
- block branch deletion;
- require linear history on `dev`;
- do not require approvals for a solo-maintained repository.

For `main`, also require the `Fresh installation` check. Restrict `main` promotions procedurally to pull requests originating from `dev`, except documented hotfixes.

Apply a tag ruleset to `v*` that blocks updates and deletion.
