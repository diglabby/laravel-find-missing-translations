<?php

declare(strict_types=1);

namespace Diglabby\FindMissingTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * Inspired by https://github.com/VetonMuhaxhiri/Laravel-find-missing-translations
 */
class FindMissingTranslations extends Command
{
    private const DEFAULT_LANG_DIRNAME = 'lang';

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'translations:missing
        {--dir= : Relative path of lang directory, e.g. "/resources/lang", a directory that contains all supported locales}
        {--base= : Base locale, e.g. "en". All other locales are compared to this locale}
        {--only= : Only compare specified locales, e.g. "be,de,es,fr". All other locales are ignored}
        {--exclude= : Exclude specified locales, e.g. "be,de,es,fr". All other locales are compared}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Helps developers to finding words which are not translated, by comparing one base locale to others.';

    private int $exitCode = self::SUCCESS;

    public function handle(): int
    {
        /** @var string|null $directoryOption */
        $directoryOption = $this->option('dir');

        $pathToLocates = match (true) {
            $directoryOption === null => resource_path(self::DEFAULT_LANG_DIRNAME),
            File::isDirectory($directoryOption) => $directoryOption,
            File::isDirectory(base_path($directoryOption)) => base_path($directoryOption),
            default => throw new DirectoryNotFoundException("Specified resource directory {$directoryOption} does not exist.")
        };

        $baseLocale = $this->option('base') ?: config('app.locale');
        assert(is_string($baseLocale), 'Invalid base locale');
        $baseLocaleDirectoryPath = $pathToLocates . \DIRECTORY_SEPARATOR . $baseLocale;

        $onlyLocales = $this->option('only');
        $onlyLocalesArray = $onlyLocales ? explode(',', $onlyLocales) : [];
        $excludeLocales = $this->option('exclude');
        $excludeLocalesArray = $excludeLocales ? explode(',', $excludeLocales) : [];

        $localeDirectories = File::directories($pathToLocates);
        $baseLocaleFiles = $this->getFilenames($baseLocaleDirectoryPath);

        foreach ($localeDirectories as $currentLocaleDirectoryPath) {
            $languageFiles = $this->getFilenames($currentLocaleDirectoryPath);
            preg_match('/(\w{2})$/', $currentLocaleDirectoryPath, $matchedParts);
            $currentLocale = $matchedParts[0];

            $isDirectoryForBaseLocale = $baseLocale === $currentLocale;
            if ($isDirectoryForBaseLocale) {
                continue;
            }
            if (count($onlyLocalesArray) > 0 && ! in_array($currentLocale, $onlyLocalesArray, true)) {
                continue;
            }
            if (in_array($currentLocale, $excludeLocalesArray, true)) {
                continue;
            }

            $this->info("Comparing {$baseLocale} to {$currentLocale}.", 'v');

            $this->compareLanguages($baseLocaleDirectoryPath, $baseLocaleFiles, $currentLocaleDirectoryPath, $languageFiles, $currentLocale);
        }

        if (count($onlyLocalesArray) > 0) {
            $locales = array_map(function ($currentLocaleDirectoryPath) {
                preg_match('/(\w{2})$/', $currentLocaleDirectoryPath, $matchedParts);

                return $matchedParts[0];
            }, $localeDirectories);
            $localesMissing = array_values(array_diff($onlyLocalesArray, $locales));
            if (count($localesMissing) > 0) {
                $this->error('The following locales are missing:', 'q');
                $this->table(['locale'], array_map(fn($locale) => [$locale], $localesMissing));
            }
        }

        $this->info('Successfully compared all languages.');

        return $this->exitCode;
    }

    /**
     * @param list<string> $baseLanguageFiles Filenames
     * @param list<string> $languageFiles Filenames
     */
    private function compareLanguages(string $baseLanguagePath, array $baseLanguageFiles, string $languagePath, array $languageFiles, string $languageName): void
    {
        foreach ($baseLanguageFiles as $languageFile) {
            $baseLanguageFile = File::getRequire("{$baseLanguagePath}/{$languageFile}");

            if (! in_array($languageFile, $languageFiles, true)) {
                $this->comment("Comparing translations in {$languageFile}.", 'v');
                $this->error("{$languageName}/{$languageFile} file is missing.", 'q');

                continue;
            }
            $secondLanguageFile = File::getRequire("{$languagePath}/{$languageFile}");

            $missingKeys = $this->arrayDiffRecursive($baseLanguageFile, $secondLanguageFile);

            if (count($missingKeys) > 0) {
                $this->exitCode = self::FAILURE;

                $this->error("Found missing translations in /{$languageName}/{$languageFile}:", 'q');

                $missingKetInfo = [];
                foreach ($missingKeys as $missingKey) {
                    $missingKetInfo[] = [$languageName, $languageFile, $missingKey];
                }

                $this->table(['locale', 'file', 'key'], $missingKetInfo);
            }
        }
    }

    /**
     * Compare array keys recursively
     * @param array<string, string|array<string, string>> $firstArray
     * @param array<string, string|array<string, string>> $secondArray
     * @return list<string>
     */
    private function arrayDiffRecursive(array $firstArray, array $secondArray): array
    {
        $outputDiff = [];

        foreach ($firstArray as $key => $value) {
            if (array_key_exists($key, $secondArray)) {
                if (is_array($value)) {
                    $recursiveDiff = $this->arrayDiffRecursive($value, $secondArray[$key]);
                    if (count($recursiveDiff)) {
                        foreach ($recursiveDiff as $diff) {
                            $outputDiff[] = $diff;
                        }
                    }
                }
            } else {
                $outputDiff[] = $key;
            }
        }

        return $outputDiff;
    }

    /**
     * Get filenames of directory
     * @return list<string> Filenames in a given directory
     */
    private function getFilenames(string $directory): array
    {
        $fileNames = [];

        $filesInFolder = File::files($directory);

        foreach ($filesInFolder as $fileInfo) {
            $fileNames[] = $fileInfo->getFilename();
        }

        return $fileNames;
    }
}
