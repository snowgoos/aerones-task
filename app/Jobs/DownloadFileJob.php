<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\DownloaderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DownloadFileJob implements ShouldQueue
{
    use Queueable;

    private array $urls;

    public function __construct(array $urls)
    {
        $this->urls = $urls;
    }

    public function handle(DownloaderInterface $downloader): void
    {
        foreach ($this->urls as $url) {
            // TODO: create concurrency call
            $downloader->download($url);
        }
    }
}
