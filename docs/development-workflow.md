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
feature/* -> dev -> main -> version tag -> manual SSH deployment
```

1. Create a temporary branch from `dev`.
2. Open a pull request into `dev`.
3. Merge with squash merge after the required CI check passes.
4. When `dev` is ready for release, open a promotion pull request from `dev` into `main`.
5. Merge the promotion pull request with a merge commit.
6. Create an immutable semantic version tag from `main`.
7. Deploy the tag or `main` manually through SSH.

Promotion pull requests must not contain release-only edits. Any required change should first be merged into `dev`.

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

Use semantic version tags:

```bash
git switch main
git pull --ff-only
git tag -a v0.1.0 -m "Jalara Vue Starter Kit v0.1.0"
git push origin v0.1.0
```

Published tags must never be moved or deleted. Create a new patch version when a release is incorrect.

## Recommended GitHub rulesets

Apply the following rules to `dev` and `main`:

- require a pull request before merging;
- require the `Quality checks` status check;
- require conversation resolution;
- block force pushes;
- block branch deletion;
- require linear history on `dev`;
- do not require approvals for a solo-maintained repository.

For `main`, also require the `Fresh installation` check. Restrict `main` promotions procedurally to pull requests originating from `dev`, except documented hotfixes.

Apply a tag ruleset to `v*` that blocks updates and deletion.
