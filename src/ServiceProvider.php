<?php

declare(strict_types=1);

namespace Diglabby\FindMissingTranslations;

use Diglabby\FindMissingTranslations\Commands\FindMissingTranslations;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

final class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FindMissingTranslations::class,
            ]);
        }
    }
}
