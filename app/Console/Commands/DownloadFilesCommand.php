<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\FileRepository;
use App\Services\ReactDownloaderService;
use Illuminate\Console\Command;

class DownloadFilesCommand extends Command
{
    protected $signature = 'downloads:run {--urls=}';
    protected $description = 'Concurrent, resumable downloader using ReactPHP';

    public function __construct(
        private readonly FileRepository $fileRepository,
        private ReactDownloaderService  $reactDownloaderService
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $urls = (string)$this->option('urls');

        $this->info("Starting downloads...");

        $files = $this->fileRepository->findAll();
        $this->reactDownloaderService->download($files);
    }
}
