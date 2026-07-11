# Automated Changelog and Release Please Design

## Goal

Automate semantic version selection, `CHANGELOG.md` maintenance, Git tags, and
GitHub Releases while preserving protected permanent branches and the existing
feature-to-development promotion flow.

## Release Flow

The repository adopts this release sequence:

```text
feature/* -> dev -> main
                    |
                    v
          Release Please PR to main
                    |
                    v
       CHANGELOG + version metadata
                    |
                    v
          tag + GitHub Release
                    |
                    v
             main -> dev sync PR
```

Temporary branches continue to target `dev` and use squash merges. Promotion
from `dev` to `main` continues to use a merge commit. Release-only edits are
made by the Release Please PR on `main`; after release, an automated sync PR
returns those edits to `dev` using the existing merge-commit policy.

## Conventional Commit Contract

Squash commit titles are the release input. Pull request titles must use
Conventional Commits:

- `feat:` produces a minor release.
- `fix:` produces a patch release.
- `feat!:`, `fix!:`, or a `BREAKING CHANGE` footer produces a major release.
- Other supported types may appear in the changelog configuration but do not
  independently force a release unless Release Please considers them
  releasable.

A pull-request workflow validates titles for temporary branches targeting
`dev`. Promotion, release, Dependabot, and `main -> dev` synchronization PRs
are explicitly exempt because their merge commits are workflow mechanics, not
release units.

GitHub labels remain useful for triage but are not versioning inputs.

## Release Please Configuration

Use `googleapis/release-please-action@v4` in manifest mode, targeting `main`.
The root package uses release type `php`, package name
`jalara-vue-starter-kit`, and tags without a component prefix so releases keep
the existing `vX.Y.Z` format.

The manifest starts at `1.0.1`, matching the latest immutable tag. A bootstrap
boundary prevents changes already included in `v1.0.1` from being repeated in
the first generated changelog.

The action runs on pushes to `main`. A normal promotion updates the pending
release PR; merging that PR causes the next action run to create the annotated
semantic tag and published GitHub Release. Release titles match tags exactly,
for example `v1.1.0`.

`CHANGELOG.md` is generated and maintained by Release Please. Humans do not
manually duplicate release entries.

## Authentication and Permissions

Use a repository secret named `RELEASE_PLEASE_TOKEN`, backed by a fine-grained
personal access token or GitHub App token scoped only to this repository. It
needs contents and pull-request write access. Using a dedicated token ensures
bot-created pull requests trigger required CI workflows; the default
`GITHUB_TOKEN` does not reliably trigger follow-on workflows.

Workflow-level permissions remain least-privilege:

- Release Please: `contents: write`, `pull-requests: write`, and `issues: write`
  for labels managed by the action.
- Title validation: `contents: read` and `pull-requests: read`.
- Synchronization: `contents: write` and `pull-requests: write`.

No token value is stored in the repository.

## Post-release Synchronization

A workflow triggered by a published release creates or updates a deterministic
sync branch and opens a PR from `main` to `dev`. It does nothing when `dev`
already contains the released commit or when an equivalent sync PR is open.

The sync PR uses the repository's established `main -> dev` merge-commit
policy. It is not auto-merged: required checks and branch protection still
gate the merge.

## Failure Handling

- Invalid PR titles fail before merge and display the expected title format.
- Release Please failures leave `main` unchanged; rerunning the workflow is
  safe because Release Please reconciles its existing PR and manifest.
- Sync creation is idempotent and must not create duplicate PRs.
- Existing tags are immutable. A failed release is corrected with a new patch
  release rather than moving or deleting a tag.
- If the dedicated token is absent, workflows fail with an explicit setup
  message rather than silently falling back to incomplete automation.

## Verification

- Validate workflow YAML and Release Please JSON configuration.
- Validate representative accepted and rejected Conventional Commit titles.
- Confirm the manifest version is `1.0.1` and the bootstrap boundary matches
  the commit tagged by `v1.0.1`.
- Run the repository's existing CI checks.
- After merge, verify Release Please opens a PR against `main` only when a
  releasable conventional commit exists.
- In the first live release, verify the generated `CHANGELOG.md`, tag name,
  release title, Latest status, and `main -> dev` sync PR.

## Documentation and Serena Memory

Update `docs/development-workflow.md`, the PR template, and contributor-facing
guidance to describe Conventional Commit titles and the new release sequence.
Update Serena's `development_workflow` memory so future agents treat Release
Please and post-release synchronization as project invariants.
