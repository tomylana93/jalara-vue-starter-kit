<?php

namespace App\Support\Localization;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * An immutable, validated view of the human-authored translation catalogs.
 *
 * English (`en`) is the canonical shape. Every selected locale is validated
 * against it for key parity, string-only leaves, placeholder parity, and
 * Laravel-compatible plural forms before any generated asset is written.
 */
final readonly class LocalizationCatalog
{
    /**
     * @param  array<string, array<string, mixed>>  $messages  Nested messages keyed by locale.
     * @param  list<string>  $keys  Sorted canonical dot-path keys.
     */
    private function __construct(
        private array $messages,
        private array $keys,
    ) {}

    /**
     * Build and validate a catalog from a language source directory.
     *
     * @param  list<string>  $locales
     */
    public static function fromDirectory(
        Filesystem $files,
        string $source,
        array $locales,
        string $canonicalLocale = 'en',
    ): self {
        $localesToLoad = array_values(array_unique([$canonicalLocale, ...$locales]));

        /** @var array<string, array<string, mixed>> $messages */
        $messages = [];
        /** @var array<string, array<string, string>> $flattened */
        $flattened = [];

        foreach ($localesToLoad as $locale) {
            $messages[$locale] = self::loadLocale($files, $source, $locale);
            $flattened[$locale] = self::flatten($locale, $messages[$locale]);
        }

        $canonicalKeys = array_keys($flattened[$canonicalLocale]);
        sort($canonicalKeys);

        foreach ($locales as $locale) {
            self::validateLocale(
                $canonicalLocale,
                $flattened[$canonicalLocale],
                $locale,
                $flattened[$locale],
            );
        }

        return new self($messages, $canonicalKeys);
    }

    /**
     * The nested messages for a locale, ready for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function messages(string $locale): array
    {
        throw_unless(
            isset($this->messages[$locale]),
            RuntimeException::class,
            "Locale [{$locale}] is not part of this catalog.",
        );

        return $this->messages[$locale];
    }

    /**
     * The sorted canonical dot-path keys used for the TypeScript union.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return $this->keys;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadLocale(Filesystem $files, string $source, string $locale): array
    {
        $directory = "{$source}/{$locale}";

        throw_unless(
            $files->isDirectory($directory),
            RuntimeException::class,
            "Locale directory [{$directory}] does not exist.",
        );

        $messages = [];

        foreach (collect($files->files($directory))->sortBy->getFilename() as $file) {
            $namespace = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            $loaded = require $file->getPathname();

            throw_unless(
                is_array($loaded),
                RuntimeException::class,
                "Language file [{$file->getPathname()}] must return an array.",
            );

            $messages[$namespace] = $loaded;
        }

        return $messages;
    }

    /**
     * Flatten nested messages into `namespace.dot.path => string` pairs.
     *
     * @param  array<string, mixed>  $messages
     * @return array<string, string>
     */
    private static function flatten(string $locale, array $messages): array
    {
        $flat = [];

        self::flattenInto($locale, '', $messages, $flat);

        return $flat;
    }

    /**
     * @param  array<string, mixed>  $messages
     * @param  array<string, string>  $flat
     */
    private static function flattenInto(string $locale, string $prefix, array $messages, array &$flat): void
    {
        foreach ($messages as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                self::flattenInto($locale, $path, $value, $flat);

                continue;
            }

            throw_unless(
                is_string($value),
                RuntimeException::class,
                "Locale [{$locale}] key [{$path}] must resolve to a string leaf.",
            );

            throw_if(
                array_key_exists($path, $flat),
                RuntimeException::class,
                "Locale [{$locale}] has a duplicate flattened key [{$path}].",
            );

            $flat[$path] = $value;
        }
    }

    /**
     * @param  array<string, string>  $canonical
     * @param  array<string, string>  $candidate
     */
    private static function validateLocale(
        string $canonicalLocale,
        array $canonical,
        string $locale,
        array $candidate,
    ): void {
        foreach ($canonical as $key => $canonicalValue) {
            throw_unless(
                array_key_exists($key, $candidate),
                RuntimeException::class,
                "Locale [{$locale}] is missing key [{$key}] present in canonical [{$canonicalLocale}].",
            );

            self::assertPlaceholderParity($locale, $key, $canonicalValue, $candidate[$key]);
            self::assertPluralForm($locale, $key, $candidate[$key]);
        }

        foreach (array_keys($candidate) as $key) {
            throw_unless(
                array_key_exists($key, $canonical),
                RuntimeException::class,
                "Locale [{$locale}] has unknown key [{$key}] absent from canonical [{$canonicalLocale}].",
            );
        }
    }

    private static function assertPlaceholderParity(
        string $locale,
        string $key,
        string $canonicalValue,
        string $candidateValue,
    ): void {
        $canonicalPlaceholders = self::placeholders($canonicalValue);
        $candidatePlaceholders = self::placeholders($candidateValue);

        throw_unless(
            $canonicalPlaceholders === $candidatePlaceholders,
            RuntimeException::class,
            "Locale [{$locale}] key [{$key}] has mismatched placeholder set [".
                implode(', ', $candidatePlaceholders).'] versus canonical ['.
                implode(', ', $canonicalPlaceholders).'].',
        );
    }

    /**
     * Extract Laravel placeholder names, normalized to lower case so that the
     * `:name`, `:Name`, and `:NAME` capitalization forms are treated as one.
     *
     * @return list<string>
     */
    private static function placeholders(string $value): array
    {
        preg_match_all('/:([A-Za-z_]\w*)/', $value, $matches);

        $names = array_map(strtolower(...), $matches[1]);
        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    private static function assertPluralForm(string $locale, string $key, string $value): void
    {
        if (! str_contains($value, '|')) {
            return;
        }

        foreach (explode('|', $value) as $segment) {
            $segment = ltrim($segment);

            if ($segment === '') {
                continue;
            }

            if (! str_starts_with($segment, '{') && ! str_starts_with($segment, '[')) {
                continue;
            }

            $selector = str_starts_with($segment, '{')
                ? preg_match('/^\{\s*(\*|-?\d+)\s*\}/', $segment) === 1
                : preg_match('/^\[\s*(\*|-?\d+)\s*,\s*(\*|-?\d+)\s*\]/', $segment) === 1;

            throw_unless(
                $selector,
                RuntimeException::class,
                "Locale [{$locale}] key [{$key}] has an invalid plural selector in segment [".trim($segment).'].',
            );
        }
    }
}
