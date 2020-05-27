<?php declare(strict_types=1);

namespace Diglabby\FindMissingTranslations\Tests\Commands;

use Diglabby\FindMissingTranslations\Commands\FindMissingTranslations;
use Diglabby\FindMissingTranslations\Tests\TestCase;

final class FindMissingTranslationsTest extends TestCase
{
    /** @test */
    public function it_does_not_report_about_synchronized_files()
    {
        $this
            ->artisan(FindMissingTranslations::class, [
                'language directory' => __DIR__.'/sync_lang_files',
                'base locale' => 'en',
            ])
            ->assertExitCode(0)
            ->expectsOutput('Successfully compared all languages.');
    }

    /** @test */
    public function it_reports_about_missing_translation_keys()
    {
        $this->withoutMockingConsoleOutput();

        $this
            ->artisan(FindMissingTranslations::class, [
                'language directory' => __DIR__.'/unsync_lang_files',
                'base locale' => 'en',
            ]);

        $output = \Artisan::output();

        $this->assertStringContainsString('Found missing translations in /be/a.php', $output);
        $this->assertStringContainsString('"OK" is not translated to /be/a.php', $output);
    }
}
