<?php

use App\Support\Localization\FrontendLocaleExporter;
use Illuminate\Filesystem\Filesystem;

beforeEach(function (): void {
    $this->sourceDir = sys_get_temp_dir().'/lang-src-'.bin2hex(random_bytes(6));
    $this->outputDir = sys_get_temp_dir().'/lang-out-'.bin2hex(random_bytes(6));

    $files = new Filesystem;
    $files->ensureDirectoryExists("{$this->sourceDir}/en");
    $files->ensureDirectoryExists("{$this->sourceDir}/id");

    $files->put("{$this->sourceDir}/en/general.php", "<?php\n\nreturn ['action' => ['save' => 'Save']];\n");
    $files->put("{$this->sourceDir}/en/auth.php", "<?php\n\nreturn ['failed' => 'Bad credentials.'];\n");
    $files->put("{$this->sourceDir}/id/general.php", "<?php\n\nreturn ['action' => ['save' => 'Simpan']];\n");
    $files->put("{$this->sourceDir}/id/auth.php", "<?php\n\nreturn ['failed' => 'Kredensial salah.'];\n");
});

afterEach(function (): void {
    $files = new Filesystem;
    $files->deleteDirectory($this->sourceDir);
    $files->deleteDirectory($this->outputDir);
});

it('exports each locale directory to a nested json file', function (): void {
    $written = app(FrontendLocaleExporter::class)->export(['en', 'id'], $this->outputDir, $this->sourceDir);

    expect($written)->toHaveKeys(['en', 'id']);

    $en = json_decode(file_get_contents("{$this->outputDir}/en.json"), true);
    $id = json_decode(file_get_contents("{$this->outputDir}/id.json"), true);

    expect($en)->toBe([
        'auth' => ['failed' => 'Bad credentials.'],
        'general' => ['action' => ['save' => 'Save']],
    ]);
    expect($id['general']['action']['save'])->toBe('Simpan');
});

it('defaults to every locale directory when none is given', function (): void {
    app(FrontendLocaleExporter::class)->export(null, $this->outputDir, $this->sourceDir);

    expect(file_exists("{$this->outputDir}/en.json"))->toBeTrue()
        ->and(file_exists("{$this->outputDir}/id.json"))->toBeTrue();
});

it('fails when a locale directory does not exist', function (): void {
    app(FrontendLocaleExporter::class)->export(['fr'], $this->outputDir, $this->sourceDir);
})->throws(RuntimeException::class);

it('fails when a language file does not return an array', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/broken.php", "<?php\n\nreturn 'not-an-array';\n");

    app(FrontendLocaleExporter::class)->export(['en'], $this->outputDir, $this->sourceDir);
})->throws(RuntimeException::class);

it('exports through the lang:export command with an output path override', function (): void {
    $this->artisan('lang:export', ['--locale' => ['en'], '--path' => $this->outputDir, '--source' => $this->sourceDir])
        ->expectsOutputToContain('Exported [en]')
        ->assertSuccessful();

    expect(file_exists("{$this->outputDir}/en.json"))->toBeTrue();
});
