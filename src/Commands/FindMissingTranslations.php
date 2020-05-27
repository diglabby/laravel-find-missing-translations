<?php declare(strict_types=1);

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
                                    {language directory? : Relative path of language directory for ex. "/resources/lang" is a directory that contains all supported languages.}
                                    {base locale? : base locale for ex. "en". All other languages are compared to this language.}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'This command helps developers to finding words which are not translated, by comparing one base locale to others.';

    /** @inheritDoc */
    public function handle(): void
    {
        if ($this->argument('language directory') === null) {
            $langDir = resource_path(self::DEFAULT_LANG_DIRNAME);
        } elseif (File::isDirectory($this->argument('language directory'))) {
            $langDir = $this->argument('language directory');
        } elseif (File::isDirectory(base_path($this->argument('language directory')))) {
            $langDir = base_path($this->argument('language directory'));
        } else {
            throw new DirectoryNotFoundException("Specified resource directory {$this->argument('language directory')} does not exist.");
        }

        $baseLocale = $this->argument('base locale') ?: config('app.locale');
        $baseLocaleDirPath = $langDir.\DIRECTORY_SEPARATOR.$baseLocale;

        $directoriesOfLanguages = File::directories($langDir);
        $baseLanguageFiles = $this->getFilenames($baseLocaleDirPath);

        foreach ($directoriesOfLanguages as $languageDirectory) {
            $languageFiles = $this->getFilenames($languageDirectory);

            $baseLanguageName = explode('/', $baseLocaleDirPath);
            $baseLanguageName = explode('\\', $baseLanguageName[count($baseLanguageName) - 1]);
            $baseLanguageName = $baseLanguageName[count($baseLanguageName) - 1];

            $languageName = explode('/', $languageDirectory);
            $languageName = explode('\\', $languageName[count($languageName) - 1]);
            $languageName = $languageName[count($languageName) - 1];

            $isDirectoryForBaseLocale = $baseLanguageName === $languageName;
            if ($isDirectoryForBaseLocale) {
                continue;
            }

            $this->info("Comparing {$baseLanguageName} to {$languageName}.", 'v');

            $this->compareLanguages($baseLocaleDirPath, $baseLanguageFiles, $languageDirectory, $languageFiles, $languageName);
        }

        $this->info('Successfully compared all languages.');
    }

    private function compareLanguages(string $baseLanguagePath, array $baseLanguageFiles, string $languagePath, array $languageFiles, string $languageName): void
    {
        foreach ($baseLanguageFiles as $languageFile) {
            $baseLanguageFile = File::getRequire("{$baseLanguagePath}/{$languageFile}");

            if (!in_array($languageFile, $languageFiles, true)) {
                $this->comment("\tComparing translations in {$languageFile}.", 'v');
                $this->error("\t{$languageName}/{$languageFile} file is missing.", 'q');
                continue;
            }
            $secondLanguageFile = File::getRequire("{$languagePath}/{$languageFile}");

            $this->compareFileKeys($baseLanguageFile, $secondLanguageFile, $languageName, $languageFile);
        }
    }

    /** Compare files and display missing translations */
    private function compareFileKeys(array $baseLanguageFileKeys, array $secondLanguageFileKeys, string $languageName, string $filename): void
    {
        $missingKeys = $this->arrayDiffRecursive($baseLanguageFileKeys, $secondLanguageFileKeys);

        if (is_array($missingKeys)) {
            if (count($missingKeys)) {
                $this->error("\tFound missing translations in /{$languageName}/{$filename}:", 'q');

                foreach ($missingKeys as $key) {
                    $this->line("\t\t\"{$key}\" is not translated to /{$languageName}/{$filename}");
                }
            }
        } else {
            $this->error("\t/{$languageName}/{$filename}: Bad file, cannot process!", 'q');
        }
    }

    /** Compare array keys recursively */
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
     */
    private function getFilenames(string $directory): array
    {
        $fileNames = [];

        /** @var \Symfony\Component\Finder\SplFileInfo[] $filesInFolder */
        $filesInFolder = File::files($directory);

        foreach ($filesInFolder as $fileInfo) {
            $fileNames[] = $fileInfo->getFilename();
        }

        return $fileNames;
    }
}
