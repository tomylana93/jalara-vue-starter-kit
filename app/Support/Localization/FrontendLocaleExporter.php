<?php

namespace App\Support\Localization;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class FrontendLocaleExporter
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Export the given locales' PHP language files to per-locale JSON assets.
     *
     * @param  list<string>|null  $locales
     * @return array<string, string>
     */
    public function export(?array $locales = null, ?string $outputDirectory = null, ?string $sourceDirectory = null): array
    {
        $sourceDirectory ??= lang_path();
        $outputDirectory ??= $sourceDirectory;
        $locales ??= $this->availableLocales($sourceDirectory);

        $this->files->ensureDirectoryExists($outputDirectory);

        $writtenFiles = [];

        foreach ($locales as $locale) {
            $localePath = "{$outputDirectory}/{$locale}.json";

            $this->files->put(
                $localePath,
                json_encode(
                    $this->messagesForLocale($sourceDirectory, $locale),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                ).PHP_EOL,
            );

            $writtenFiles[$locale] = $localePath;
        }

        return $writtenFiles;
    }

    /**
     * @return list<string>
     */
    private function availableLocales(string $sourceDirectory): array
    {
        /** @var list<string> $locales */
        $locales = collect($this->files->directories($sourceDirectory))
            ->map(static fn (string $directory): string => basename($directory))
            ->sort()
            ->values()
            ->all();

        return $locales;
    }

    /**
     * @return array<string, mixed>
     */
    private function messagesForLocale(string $sourceDirectory, string $locale): array
    {
        $directory = "{$sourceDirectory}/{$locale}";

        throw_unless($this->files->isDirectory($directory), RuntimeException::class, "Locale directory [{$directory}] does not exist.");

        $messages = [];

        foreach (collect($this->files->files($directory))->sortBy->getFilename() as $file) {
            $namespace = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $messages[$namespace] = $this->loadMessages($file->getPathname());
        }

        return $messages;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadMessages(string $path): array
    {
        $messages = require $path;

        throw_unless(is_array($messages), RuntimeException::class, "Language file [{$path}] must return an array.");

        return $messages;
    }
}
