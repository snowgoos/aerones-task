<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\DownloaderInterface;
use App\Services\ReactDownloader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->bind(DownloaderInterface::class, ReactDownloader::class);
    }

    public function boot(): void
    {
        //
    }
}
