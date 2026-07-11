# Automated Changelog with Release Please Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Automate `CHANGELOG.md`, semantic version tags, GitHub Releases, and post-release `main -> dev` synchronization with Release Please.

**Architecture:** Release Please runs in manifest mode after pushes to `main`, maintains one release PR, and publishes the tag and GitHub Release when that PR merges. Separate narrowly scoped workflows validate Conventional Commit PR titles and open an idempotent synchronization PR from `main` back to `dev` after publication.

**Tech Stack:** GitHub Actions, `googleapis/release-please-action@v4`, `amannn/action-semantic-pull-request@v6`, Release Please PHP strategy, Pest 4, GitHub CLI.

## Global Constraints

- Temporary branches still target `dev`; promotion remains `dev -> main` with a merge commit.
- Release-only changes are made by a Release Please PR against `main`, then synchronized through a `main -> dev` PR.
- Squash titles for temporary PRs must follow Conventional Commits.
- Existing tag `v1.0.1` is immutable and is the Release Please starting version.
- Release and synchronization workflows use `RELEASE_PLEASE_TOKEN`; no token value is committed.
- Workflows use minimum permissions and never bypass protected branches.
- Do not add Composer or npm dependencies.

---

### Task 1: Lock Release Automation Contracts with Pest

**Files:**
- Create: `tests/Unit/ReleaseAutomationConfigurationTest.php`
- Test: `tests/Unit/ReleaseAutomationConfigurationTest.php`

**Interfaces:**
- Consumes: repository files through `base_path()`.
- Produces: executable assertions for Release Please state, workflow triggers, credentials, permissions, and synchronization behavior.

- [ ] **Step 1: Create the failing configuration contract tests**

```php
<?php

declare(strict_types=1);

function repositoryFile(string $path): string
{
    $contents = file_get_contents(base_path($path));

    if ($contents === false) {
        throw new RuntimeException("Unable to read repository file: {$path}");
    }

    return $contents;
}

test('release please starts from the latest immutable release', function () {
    $manifest = json_decode(repositoryFile('.release-please-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $configuration = json_decode(repositoryFile('release-please-config.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)->toBe(['.' => '1.0.1'])
        ->and($configuration['release-type'])->toBe('php')
        ->and($configuration['include-component-in-tag'])->toBeFalse()
        ->and($configuration['packages']['.']['package-name'])->toBe('jalara-vue-starter-kit');
});

test('release please workflow targets main and uses the dedicated token', function () {
    $workflow = repositoryFile('.github/workflows/release-please.yml');

    expect($workflow)->toContain('branches: [main]')
        ->toContain('googleapis/release-please-action@v4')
        ->toContain('target-branch: main')
        ->toContain('RELEASE_PLEASE_TOKEN')
        ->toContain('contents: write')
        ->toContain('pull-requests: write');
});

test('temporary pull request titles are validated', function () {
    $workflow = repositoryFile('.github/workflows/pr-title.yml');

    expect($workflow)->toContain('branches: [dev]')
        ->toContain("github.head_ref != 'main'")
        ->toContain('amannn/action-semantic-pull-request@v6')
        ->toContain('types: |')
        ->toContain('feat')
        ->toContain('fix');
});

test('published releases create an idempotent main to dev sync pull request', function () {
    $workflow = repositoryFile('.github/workflows/sync-release.yml');

    expect($workflow)->toContain('types: [published]')
        ->toContain('RELEASE_PLEASE_TOKEN')
        ->toContain('--base dev --head main')
        ->toContain('gh pr list')
        ->toContain('gh pr create');
});
```

- [ ] **Step 2: Run the focused tests and verify they fail because configuration files do not exist**

Run: `php artisan test --compact tests/Unit/ReleaseAutomationConfigurationTest.php`

Expected: FAIL with `file_get_contents(...): Failed to open stream` or an expectation failure for the missing files.

- [ ] **Step 3: Commit the failing contract tests**

```bash
git add tests/Unit/ReleaseAutomationConfigurationTest.php
git commit -m "test: define release automation contracts"
```

### Task 2: Configure Release Please and Automatic GitHub Releases

**Files:**
- Create: `release-please-config.json`
- Create: `.release-please-manifest.json`
- Create: `.github/workflows/release-please.yml`
- Test: `tests/Unit/ReleaseAutomationConfigurationTest.php`

**Interfaces:**
- Consumes: Conventional Commit squash messages on `main`, repository secret `RELEASE_PLEASE_TOKEN`, current version `1.0.1`.
- Produces: Release Please PRs targeting `main`, generated `CHANGELOG.md`, `vX.Y.Z` tags, and published GitHub Releases named exactly like their tags.

- [ ] **Step 1: Add the manifest configuration**

Create `release-please-config.json`:

```json
{
    "$schema": "https://raw.githubusercontent.com/googleapis/release-please/main/schemas/config.json",
    "release-type": "php",
    "include-component-in-tag": false,
    "include-v-in-tag": true,
    "bootstrap-sha": "8e11a97f73bcbfa02d01ed424da87bc00aeada81",
    "packages": {
        ".": {
            "package-name": "jalara-vue-starter-kit",
            "changelog-path": "CHANGELOG.md",
            "release-name-pattern": "v${version}"
        }
    }
}
```

Create `.release-please-manifest.json`:

```json
{
    ".": "1.0.1"
}
```

- [ ] **Step 2: Add the Release Please workflow**

Create `.github/workflows/release-please.yml`:

```yaml
name: Release Please

on:
  push:
    branches: [main]

permissions:
  contents: write
  issues: write
  pull-requests: write

jobs:
  release-please:
    name: Prepare or publish release
    runs-on: ubuntu-latest
    steps:
      - name: Require release token
        env:
          RELEASE_PLEASE_TOKEN: ${{ secrets.RELEASE_PLEASE_TOKEN }}
        run: |
          if [ -z "$RELEASE_PLEASE_TOKEN" ]; then
            echo "RELEASE_PLEASE_TOKEN must be configured" >&2
            exit 1
          fi

      - name: Run Release Please
        uses: googleapis/release-please-action@v4
        with:
          token: ${{ secrets.RELEASE_PLEASE_TOKEN }}
          target-branch: main
          config-file: release-please-config.json
          manifest-file: .release-please-manifest.json
```

- [ ] **Step 3: Validate both JSON files**

Run: `jq empty release-please-config.json .release-please-manifest.json`

Expected: exit code 0 with no output.

- [ ] **Step 4: Run the Release Please contract tests**

Run: `php artisan test --compact tests/Unit/ReleaseAutomationConfigurationTest.php --filter='release please'`

Expected: 2 tests pass.

- [ ] **Step 5: Commit Release Please configuration**

```bash
git add release-please-config.json .release-please-manifest.json .github/workflows/release-please.yml
git commit -m "ci: configure Release Please"
```

### Task 3: Enforce Conventional Pull Request Titles

**Files:**
- Create: `.github/workflows/pr-title.yml`
- Modify: `.github/pull_request_template.md`
- Test: `tests/Unit/ReleaseAutomationConfigurationTest.php`

**Interfaces:**
- Consumes: PR metadata for requests targeting `dev`.
- Produces: required `Validate PR title` check for temporary branches; `main -> dev` synchronization is exempt.

- [ ] **Step 1: Add PR title validation**

Create `.github/workflows/pr-title.yml`:

```yaml
name: Pull request title

on:
  pull_request_target:
    branches: [dev]
    types: [opened, edited, reopened, synchronize]

permissions:
  pull-requests: read

jobs:
  validate:
    name: Validate PR title
    if: github.head_ref != 'main'
    runs-on: ubuntu-latest
    steps:
      - name: Validate Conventional Commit title
        uses: amannn/action-semantic-pull-request@v6
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          types: |
            build
            chore
            ci
            docs
            feat
            fix
            perf
            refactor
            revert
            style
            test
          requireScope: false
          subjectPattern: ^(?![A-Z]).+$
          subjectPatternError: The subject must start with a lowercase character.
```

- [ ] **Step 2: Add title guidance to the PR template**

Insert immediately below the title in `.github/pull_request_template.md`:

```markdown
> Temporary PR titles must follow Conventional Commits, for example
> `feat: add reporting dashboard` or `fix: correct login throttling`.
> Use `!` or a `BREAKING CHANGE` footer for a major release.
```

- [ ] **Step 3: Run the title validation contract test**

Run: `php artisan test --compact tests/Unit/ReleaseAutomationConfigurationTest.php --filter='temporary pull request titles'`

Expected: 1 test passes.

- [ ] **Step 4: Commit the title policy**

```bash
git add .github/workflows/pr-title.yml .github/pull_request_template.md
git commit -m "ci: validate conventional pull request titles"
```

### Task 4: Create Idempotent Post-release Synchronization

**Files:**
- Create: `.github/workflows/sync-release.yml`
- Test: `tests/Unit/ReleaseAutomationConfigurationTest.php`

**Interfaces:**
- Consumes: `release.published` event and `RELEASE_PLEASE_TOKEN`.
- Produces: at most one open PR with base `dev` and head `main`, titled `chore: sync main after <tag>`.

- [ ] **Step 1: Add the synchronization workflow**

Create `.github/workflows/sync-release.yml`:

```yaml
name: Sync released main to dev

on:
  release:
    types: [published]

permissions:
  contents: read
  pull-requests: write

jobs:
  sync:
    name: Open main to dev synchronization PR
    runs-on: ubuntu-latest
    steps:
      - name: Require release token
        env:
          RELEASE_PLEASE_TOKEN: ${{ secrets.RELEASE_PLEASE_TOKEN }}
        run: |
          if [ -z "$RELEASE_PLEASE_TOKEN" ]; then
            echo "RELEASE_PLEASE_TOKEN must be configured" >&2
            exit 1
          fi

      - name: Open synchronization pull request
        env:
          GH_TOKEN: ${{ secrets.RELEASE_PLEASE_TOKEN }}
          GH_REPO: ${{ github.repository }}
          RELEASE_TAG: ${{ github.event.release.tag_name }}
        run: |
          existing_pr="$(gh pr list --state open --base dev --head main --json number --jq '.[0].number // empty')"

          if [ -n "$existing_pr" ]; then
            echo "Synchronization PR #$existing_pr is already open"
            exit 0
          fi

          comparison="$(gh api "repos/$GH_REPO/compare/dev...main" --jq '.ahead_by')"

          if [ "$comparison" -eq 0 ]; then
            echo "dev already contains main"
            exit 0
          fi

          gh pr create \
            --base dev \
            --head main \
            --title "chore: sync main after $RELEASE_TAG" \
            --body "Synchronize release metadata and CHANGELOG.md from $RELEASE_TAG back into dev. Merge this PR with a merge commit."
```

- [ ] **Step 2: Run the synchronization contract test**

Run: `php artisan test --compact tests/Unit/ReleaseAutomationConfigurationTest.php --filter='published releases'`

Expected: 1 test passes.

- [ ] **Step 3: Commit synchronization automation**

```bash
git add .github/workflows/sync-release.yml
git commit -m "ci: synchronize releases back to dev"
```

### Task 5: Document and Persist the New Release Invariants

**Files:**
- Modify: `docs/development-workflow.md`
- Modify: `README.md`
- Modify: `.serena/memories/development_workflow.md`
- Test: `tests/Unit/ReleaseAutomationConfigurationTest.php`

**Interfaces:**
- Consumes: implemented workflows and repository secret name.
- Produces: contributor instructions and durable Serena context aligned with automation.

- [ ] **Step 1: Replace the normal release flow in the development workflow document**

Update `docs/development-workflow.md` so its normal-flow diagram is:

```text
feature/* -> dev -> main -> Release Please PR -> version tag -> GitHub Release
                                  |
                                  v
                             main -> dev
```

State explicitly:

```markdown
Temporary pull requests use Conventional Commit titles. Release Please reads
the squash commit produced from those titles: `feat` bumps minor, `fix` bumps
patch, and `!` or `BREAKING CHANGE` bumps major.

After a promotion PR is merged, Release Please opens or updates a release PR
against `main`. Merging the release PR updates `CHANGELOG.md`, creates the tag,
and publishes the GitHub Release. The published-release workflow then opens a
`main` to `dev` synchronization PR, which is merged with a merge commit.

Repository administrators must configure `RELEASE_PLEASE_TOKEN` with contents
and pull-request write access. Never store the token in the repository.
```

Remove the old manual tag commands and retain the invariant that published tags are never moved or deleted.

- [ ] **Step 2: Update the README release summary**

Replace the branch-flow line in `README.md` with:

```text
feature/* -> dev -> main -> Release Please PR -> version tag -> GitHub Release -> main/dev sync
```

Add one sentence linking `docs/development-workflow.md` for the Conventional Commit title and Release Please setup requirements.

- [ ] **Step 3: Rewrite Serena's workflow memory**

Use Serena `write_memory` for `development_workflow` with these durable facts:

```markdown
# Development and Release Workflow
- Normal path: temporary branch from `dev` -> squash PR into `dev` -> promotion merge-commit PR `dev` to `main` -> Release Please PR into `main` -> automated immutable semantic tag and GitHub Release -> merge-commit sync PR `main` to `dev`.
- Temporary PR titles follow Conventional Commits; `feat` bumps minor, `fix` bumps patch, and `!`/`BREAKING CHANGE` bumps major.
- Release Please owns `CHANGELOG.md`, version metadata, tag creation, and GitHub Release publication; do not create these manually.
- Release automation requires repository secret `RELEASE_PLEASE_TOKEN`; never write its value to files or logs.
- Direct pushes to `dev` and `main` remain prohibited. Release and sync PRs must pass branch protection.
- Published `v*` tags are immutable; correct a bad release with a new patch version.
- Hotfixes still flow `main -> hotfix/* -> main`, then synchronize `main -> dev`.
- Canonical details: docs/development-workflow.md.
```

- [ ] **Step 4: Run all release automation tests**

Run: `php artisan test --compact tests/Unit/ReleaseAutomationConfigurationTest.php`

Expected: 4 tests pass.

- [ ] **Step 5: Commit documentation and memory**

```bash
git add docs/development-workflow.md README.md .serena/memories/development_workflow.md
git commit -m "docs: describe automated release workflow"
```

### Task 6: Complete Repository-wide Verification

**Files:**
- Verify: all files changed in Tasks 1-5

**Interfaces:**
- Consumes: complete automation implementation.
- Produces: evidence that repository checks pass and the branch is ready for review.

- [ ] **Step 1: Validate JSON and whitespace**

Run:

```bash
jq empty release-please-config.json .release-please-manifest.json
git diff --check dev...HEAD
```

Expected: both commands exit 0 with no output.

- [ ] **Step 2: Run the focused Pest suite**

Run: `php artisan test --compact tests/Unit/ReleaseAutomationConfigurationTest.php`

Expected: 4 tests pass.

- [ ] **Step 3: Run the complete CI-equivalent suite**

Run: `composer run ci:check`

Expected: ESLint, Prettier, Vue TypeScript, Pint, Larastan, and all Pest tests pass.

- [ ] **Step 4: Review the final branch diff and commit state**

Run:

```bash
git status -sb
git log --oneline dev..HEAD
git diff --stat dev...HEAD
```

Expected: clean worktree; only the planned commits and release-automation files are present.

- [ ] **Step 5: Record required repository setup in the handoff**

State exactly:

```text
Before merging, configure RELEASE_PLEASE_TOKEN in GitHub repository secrets and add "Validate PR title" to the dev branch required checks after its first run. After the feature reaches main, verify Release Please opens its first release PR from version 1.0.1.
```
