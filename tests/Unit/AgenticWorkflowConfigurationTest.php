<?php

declare(strict_types=1);

function agentWorkflowFile(string $path): string
{
    $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$path);

    throw_if($contents === false, RuntimeException::class, "Unable to read agent workflow file: {$path}");

    return $contents;
}

test('agentic workflow declares the required operating constraints', function () {
    $workflow = agentWorkflowFile('.agents/workflow.yaml');

    expect($workflow)
        ->toContain('base_branch: dev')
        ->toContain('one_task_one_orchestrator')
        ->toContain('one_branch_one_writer')
        ->toContain('parallel_read_only_agents_only')
        ->toContain('cross_provider_review')
        ->toContain('maximum_fix_cycles: 2');
});

test('agentic workflow policies and contracts are present', function () {
    $requiredFiles = [
        '.agents/policies/risk.yaml',
        '.agents/policies/routing.yaml',
        '.agents/policies/approvals.yaml',
        '.agents/policies/tools.yaml',
        '.agents/contracts/task-packet.md',
        '.agents/contracts/handoff.md',
        '.agents/contracts/review-finding.md',
        '.agents/skills/jalara-agentic-workflow/SKILL.md',
        'docs/agents/WORKFLOW.md',
    ];

    foreach ($requiredFiles as $file) {
        expect(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$file)->toBeFile();
    }
});

test('agent gate runs the existing ci checks and frontend build', function () {
    $composer = json_decode(agentWorkflowFile('composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['scripts'])->toHaveKey('agent:gate')
        ->and($composer['scripts']['agent:gate'])->toContain('@ci:check')
        ->and($composer['scripts']['agent:gate'])->toContain('pnpm run build');
});
