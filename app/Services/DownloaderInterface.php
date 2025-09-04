<?php

declare(strict_types=1);

namespace App\Services;

interface DownloaderInterface
{
    public function download(iterable $files, int $retryCount = 0): void;

    public function setStorage(): void;
}
