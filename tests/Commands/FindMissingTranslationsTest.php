<?php declare(strict_types=1);

namespace Diglabby\FindMissingTranslations\Tests\Commands;

use Diglabby\FindMissingTranslations\Commands\FindMissingTranslations;
use Diglabby\FindMissingTranslations\Tests\TestCase;

final class FindMissingTranslationsTest extends TestCase
{
    /** @test */
    public function it_does_not_report_about_synchronized_files()
    {
        $this->withoutMockingConsoleOutput();

        $this
            ->artisan(FindMissingTranslations::class, [
                '--dir' => __DIR__.'/sync_lang_files',
                '--base' => 'en',
            ]);
        $output = \Artisan::output();

        $this->assertSame('Successfully compared all languages.', trim($output));
    }

    /** @test */
    public function it_reports_about_missing_translation_keys()
    {
        $this->withoutMockingConsoleOutput();

        $this
            ->artisan(FindMissingTranslations::class, [
                '--dir' => __DIR__.'/unsync_lang_files',
                '--base' => 'en',
            ]);
        $output = \Artisan::output();

        $this->assertStringContainsString('Found missing translations in /be/a.php', $output);
        $this->assertStringContainsString('"OK" is not translated to /be/a.php', $output);
    }
}
