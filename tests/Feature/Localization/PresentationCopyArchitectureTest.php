<?php

use Illuminate\Filesystem\Filesystem;

/** @return list<string> */
function presentationLiteralPatterns(): array
{
    return [
        '/[\'\"](?:error|message|status|subject|title)[\'\"]\s*=>\s*[\'\"][A-Z]/',
        '/->(?:line|subject)\(\s*[\'\"][A-Z]/',
        '/->(?:add|with)\(\s*[\'\"][^\'\"]+[\'\"]\s*,\s*[\'\"][A-Z]/',
    ];
}

function containsPresentationLiteral(string $source, string $path): bool
{
    $matchesSink = collect(presentationLiteralPatterns())->contains(
        static fn (string $pattern): bool => preg_match($pattern, $source) === 1,
    );
    $matchesFormRequestMessage = str_contains(
        $path,
        DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Requests'.DIRECTORY_SEPARATOR,
    ) && preg_match('/=>\s*[\'\"][A-Z][^\'\"]+[.!?][\'\"]/', $source) === 1;

    return $matchesSink || $matchesFormRequestMessage;
}

it('recognizes untranslated presentation sink examples', function (string $source, string $path): void {
    expect(containsPresentationLiteral($source, $path))->toBeTrue();
})->with([
    'flash payload' => ["Inertia::flash('toast', ['message' => 'Saved.']);", 'app/Http/Controllers/Example.php'],
    'session status' => ["return back()->with('status', 'Saved.');", 'app/Http/Controllers/Example.php'],
    'validation error' => ["\$errors->add('email', 'Invalid email.');", 'app/Actions/Example.php'],
    'form request message' => ["return ['email.required' => 'Email is required.'];", 'app/Http/Requests/ExampleRequest.php'],
    'notification subject' => ["return (new MailMessage)->subject('Welcome');", 'app/Notifications/Example.php'],
    'notification line' => ["return (new MailMessage)->line('Hello.');", 'app/Notifications/Example.php'],
]);

it('does not use English sentences as translation keys', function (): void {
    $files = new Filesystem;
    $violations = [];

    foreach ($files->allFiles(app_path()) as $file) {
        if (str_starts_with($file->getRelativePathname(), 'Console'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $contents = $files->get($file->getPathname());

        if (preg_match('/\b(?:__|trans)\(\s*([\'\"])[A-Z][^\'\"]+[.!?]\1/', $contents) === 1) {
            $violations[] = $file->getRelativePathname();
        }
    }

    expect($violations)->toBe([]);
});

it('does not put untranslated literals in presentation message payloads', function (): void {
    $files = new Filesystem;
    $directories = array_filter([
        app_path('Actions'),
        app_path('Http/Controllers'),
        app_path('Http/Requests'),
        app_path('Mail'),
        app_path('Notifications'),
    ], $files->isDirectory(...));
    $violations = [];

    foreach ($directories as $directory) {
        foreach ($files->allFiles($directory) as $file) {
            $contents = $files->get($file->getPathname());

            if (containsPresentationLiteral($contents, $file->getPathname())) {
                $violations[] = $file->getRelativePathname();
            }
        }
    }

    expect($violations)->toBe([]);
});
