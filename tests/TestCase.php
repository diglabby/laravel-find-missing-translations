<?php declare(strict_types=1);

namespace Diglabby\FindMissingTranslations\Tests;

use Diglabby\FindMissingTranslations\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     * In a normal app environment these would be added to the 'providers' array in the config/app.php file.
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }
}
