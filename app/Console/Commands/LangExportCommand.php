<?php

namespace App\Console\Commands;

use App\Support\Localization\FrontendLocaleExporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('lang:export
    {--locale=* : The locales to export}
    {--path= : Override the output directory}
    {--source= : Override the source language directory}
    {--types-path= : Override the generated TypeScript key contract path}')]
#[Description('Export Laravel language files to frontend JSON assets')]
class LangExportCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FrontendLocaleExporter $exporter): int
    {
        try {
            $writtenFiles = $exporter->export($this->locales(), $this->outputPath(), $this->sourcePath(), $this->typesPath());
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        foreach ($writtenFiles as $locale => $path) {
            $this->info("Exported [{$locale}] to [{$path}].");
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>|null
     */
    private function locales(): ?array
    {
        /** @var array<int, string|null> $locales */
        $locales = $this->option('locale');

        /** @var list<string> $filteredLocales */
        $filteredLocales = array_values(array_filter(
            $locales,
            static fn (mixed $locale): bool => is_string($locale) && $locale !== '',
        ));

        return $filteredLocales === [] ? null : $filteredLocales;
    }

    private function outputPath(): ?string
    {
        return $this->stringOption('path');
    }

    private function sourcePath(): ?string
    {
        return $this->stringOption('source');
    }

    private function typesPath(): ?string
    {
        return $this->stringOption('types-path');
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
