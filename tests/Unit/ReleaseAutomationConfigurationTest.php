<?php

declare(strict_types=1);

function repositoryFile(string $path): string
{
    $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$path);

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
