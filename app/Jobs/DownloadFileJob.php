<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\FileRepository;
use App\Services\DownloaderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DownloadFileJob implements ShouldQueue
{
    use Queueable;

    private array $ids;

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    public function handle(
        FileRepository      $fileRepository,
        DownloaderInterface $downloader
    ): void {
        $files = $fileRepository->getWhereIdIn($this->ids);
        $downloader->download($files);
    }
}
