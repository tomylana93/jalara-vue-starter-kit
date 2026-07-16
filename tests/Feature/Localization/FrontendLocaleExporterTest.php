<?php

use App\Support\Localization\FrontendLocaleExporter;
use Illuminate\Filesystem\Filesystem;

beforeEach(function (): void {
    $this->sourceDir = sys_get_temp_dir().'/lang-src-'.bin2hex(random_bytes(6));
    $this->outputDir = sys_get_temp_dir().'/lang-out-'.bin2hex(random_bytes(6));
    $this->typesPath = "{$this->outputDir}/translation.generated.ts";

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

/**
 * @param  list<string>|null  $locales
 * @return array<string, string>
 */
function runExport(mixed $test, ?array $locales = ['en', 'id']): array
{
    return app(FrontendLocaleExporter::class)->export(
        $locales,
        $test->outputDir,
        $test->sourceDir,
        $test->typesPath,
    );
}

it('exports each locale directory to a nested json file', function (): void {
    $written = runExport($this);

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
    runExport($this, null);

    expect(file_exists("{$this->outputDir}/en.json"))->toBeTrue()
        ->and(file_exists("{$this->outputDir}/id.json"))->toBeTrue();
});

it('fails when a locale directory does not exist', function (): void {
    runExport($this, ['fr']);
})->throws(RuntimeException::class);

it('fails when a language file does not return an array', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/broken.php", "<?php\n\nreturn 'not-an-array';\n");

    runExport($this, ['en']);
})->throws(RuntimeException::class);

it('exports through the lang:export command with an output path override', function (): void {
    $this->artisan('lang:export', [
        '--locale' => ['en', 'id'],
        '--path' => $this->outputDir,
        '--source' => $this->sourceDir,
        '--types-path' => $this->typesPath,
    ])
        ->expectsOutputToContain('Exported [en]')
        ->assertSuccessful();

    expect(file_exists("{$this->outputDir}/en.json"))->toBeTrue()
        ->and(file_exists($this->typesPath))->toBeTrue();
});

it('generates a deterministic sorted TranslationKey union', function (): void {
    runExport($this);

    $types = file_get_contents($this->typesPath);

    expect($types)->toContain('export type TranslationKey =')
        ->and($types)->toContain("| 'auth.failed'")
        ->and($types)->toContain("| 'general.action.save'");

    // auth.failed must sort before general.action.save.
    expect(strpos($types, "'auth.failed'"))->toBeLessThan(strpos($types, "'general.action.save'"));

    // A second export is byte-for-byte identical.
    $first = file_get_contents($this->typesPath);
    runExport($this);
    expect(file_get_contents($this->typesPath))->toBe($first);
});

it('rejects missing and extra keys with locale and key context', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['action' => ['cancel' => 'Batal']];\n");

    expect(fn () => runExport($this))
        ->toThrow(RuntimeException::class, 'Locale [id]');
});

it('rejects non-string leaves', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/general.php", "<?php\nreturn ['action' => ['save' => 42]];\n");
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['action' => ['save' => 'Simpan']];\n");

    expect(fn () => runExport($this))
        ->toThrow(RuntimeException::class);
});

it('rejects placeholder mismatches', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/general.php", "<?php\nreturn ['welcome' => 'Hello :name'];\n");
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['welcome' => 'Halo :user'];\n");

    expect(fn () => runExport($this))
        ->toThrow(RuntimeException::class, 'placeholder');
});

it('accepts placeholder capitalization variants across locales', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/general.php", "<?php\nreturn ['welcome' => 'Hello :name'];\n");
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['welcome' => 'Halo :Name'];\n");

    expect(fn () => runExport($this))->not->toThrow(RuntimeException::class);
});

it('rejects duplicate flattened paths', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/general.php", "<?php\nreturn ['action' => ['save' => 'Save'], 'action.save' => 'Dup'];\n");
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['action' => ['save' => 'Simpan'], 'action.save' => 'Dup'];\n");

    expect(fn () => runExport($this))
        ->toThrow(RuntimeException::class, 'duplicate');
});

it('accepts valid Laravel plural forms', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/general.php", "<?php\nreturn ['files' => '{0} No files|{1} One file|[2,*] :count files'];\n");
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['files' => '{0} Tidak ada berkas|{1} Satu berkas|[2,*] :count berkas'];\n");

    expect(fn () => runExport($this))->not->toThrow(RuntimeException::class);
});

it('rejects invalid plural intervals', function (): void {
    (new Filesystem)->put("{$this->sourceDir}/en/general.php", "<?php\nreturn ['files' => '{0} No files|[2,] :count files'];\n");
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['files' => '{0} Tidak ada|[2,] :count berkas'];\n");

    expect(fn () => runExport($this))
        ->toThrow(RuntimeException::class, 'plural');
});

it('leaves pre-existing generated files intact when validation fails', function (): void {
    // First, a successful export.
    runExport($this);

    $enBytes = file_get_contents("{$this->outputDir}/en.json");
    $idBytes = file_get_contents("{$this->outputDir}/id.json");
    $typeBytes = file_get_contents($this->typesPath);

    // Now break parity so validation fails on the next export.
    (new Filesystem)->put("{$this->sourceDir}/id/general.php", "<?php\nreturn ['action' => ['cancel' => 'Batal']];\n");

    expect(fn () => runExport($this))->toThrow(RuntimeException::class);

    // Pre-existing generated files must be byte-for-byte intact.
    expect(file_get_contents("{$this->outputDir}/en.json"))->toBe($enBytes)
        ->and(file_get_contents("{$this->outputDir}/id.json"))->toBe($idBytes)
        ->and(file_get_contents($this->typesPath))->toBe($typeBytes);
});

it('removes newly installed targets when a later atomic move fails', function (): void {
    $files = new class extends Filesystem
    {
        private int $installMoves = 0;

        private bool $failed = false;

        public function move($path, $target)
        {
            if (str_ends_with($path, '.exporting')) {
                $this->installMoves++;

                if ($this->installMoves === 2 && ! $this->failed) {
                    $this->failed = true;

                    throw new RuntimeException('Injected move failure.');
                }
            }

            return parent::move($path, $target);
        }
    };

    $exporter = new FrontendLocaleExporter($files);

    expect(fn () => $exporter->export(
        ['en', 'id'],
        $this->outputDir,
        $this->sourceDir,
        $this->typesPath,
    ))->toThrow(RuntimeException::class, 'Injected move failure.');

    expect(file_exists("{$this->outputDir}/en.json"))->toBeFalse()
        ->and(file_exists("{$this->outputDir}/id.json"))->toBeFalse()
        ->and(file_exists($this->typesPath))->toBeFalse();
});
