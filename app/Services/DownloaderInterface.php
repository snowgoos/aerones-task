<?php

declare(strict_types=1);

namespace App\Services;

interface DownloaderInterface
{
    public function download(string $url): void;
}
