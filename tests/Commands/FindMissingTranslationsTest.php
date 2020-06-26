<?php declare(strict_types=1);

namespace Diglabby\FindMissingTranslations\Tests\Commands;

use Diglabby\FindMissingTranslations\Commands\FindMissingTranslations;
use Diglabby\FindMissingTranslations\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

final class FindMissingTranslationsTest extends TestCase
{
    /** @test */
    public function it_does_not_report_about_synchronized_files()
    {
        $this->withoutMockingConsoleOutput();

        $dir = __DIR__.'/sync_lang_files';
        $exitCode = $this->artisan("translations:missing --dir=$dir --base=en");
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame('Successfully compared all languages.', trim($output));
    }

    /** @test */
    public function it_reports_about_missing_translation_keys()
    {
        $this->withoutMockingConsoleOutput();

        $dir = __DIR__.'/unsync_lang_files';
        $exitCode = $this->artisan("translations:missing --dir=$dir --base=en");
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('| be     | a.php | OK  |', $output);
    }
}
