<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\Http\Browser;
use React\Promise\Deferred;
use React\Stream\WritableResourceStream;

class ReactDownloader implements DownloaderInterface
{
    private string $tmpDir;
    private string $completedDir;

    public function __construct(private readonly FileRepository $fileRepository)
    {
        $this->tmpDir = storage_path('app/downloads/tmp');
        $this->completedDir = storage_path('app/downloads/completed');

        @mkdir($this->tmpDir, 0777, true);
        @mkdir($this->completedDir, 0777, true);
    }

    public function download(string $url): void
    {
        $file = $this->fileRepository->findOneBy(['url' => $url]);

        if ($file === null) {
            return;
        }

        $headers = [];
        $loop = Loop::get();
        $browser = new Browser($loop);
        $deferred = new Deferred();
        $fileName = $file->name;
        $tempFilePath = $this->tmpDir . "/" . $fileName;
        $completedFilePath = $this->completedDir . "/" . $fileName;

        $logData = [
            'name' => $fileName,
            'url'  => $file->url,
        ];

        $this->logger("Starting download process", $logData);

        if (file_exists($tempFilePath)) {
            $this->updateStatus($file, File::STATUS_COMPLETED);

            return;
        }

        $offset = file_exists($tempFilePath) ? filesize($tempFilePath) : 0;

        if ($offset > 0) {
            $headers['Range'] = "bytes=" . $offset . "-";
        }

        $this->updateStatus($file, File::STATUS_IN_PROGRESS);

        $fp = fopen($tempFilePath, 'ab+');
        $writable = new WritableResourceStream($fp, $loop);

        if ($writable->isWritable() === false) {
            $this->logger("Failed to open file for writing", $logData);
            $deferred->reject(new \RuntimeException("Failed to open file for writing"));

            return;
        }

        // TODO: split by methods
        $browser->requestStreaming('GET', $url, $headers)
            ->then(
                function (ResponseInterface $response) use (
                    $writable,
                    $deferred,
                    $url,
                    $tempFilePath,
                    $completedFilePath,
                    $file,
                    $logData
                ) {
                    $body = $response->getBody();

                    // Save total size if Content-Length is available
                    if ($response->hasHeader('Content-Length')) {
                        $file->update(['size' => (int)$response->getHeaderLine('Content-Length')]);
                    }

                    $body->on('data', function ($chunk) use ($writable, $tempFilePath, $file) {
                        $this->updateStatus($file, File::STATUS_IN_PROGRESS);
                    });

                    $body->on('end', function () use ($writable, $tempFilePath, $completedFilePath, $file) {
                        $writable->end();
                        rename($tempFilePath, $completedFilePath);

                        $file->status = File::STATUS_COMPLETED;
                        $file->progress = filesize($completedFilePath);
                    });

                    $body->on('error', function ($error) use (
                        $writable,
                        $deferred,
                        $file,
                        $logData
                    ) {
                        $writable->end();
                        $this->logger('Download stream error', $logData);

                        $this->updateStatus($file, File::STATUS_FAILED);
                        $deferred->reject($error);
                    });
                },
                function (Throwable $e) use ($url, $file) {
                    $file->status = File::STATUS_FAILED;

                    Log::error("Download failed for {$url}: " . $e->getMessage());
                }
            )
            ->otherwise(function ($error) use (
                $writable,
                $deferred,
                $file,
                $logData,
                $retryCount
            ) {
                if (is_resource($fileHandle)) {
                    fclose($fileHandle);
                }

                $logData['error'] = $error->getMessage();
                $this->logger("Download request failed", $logData);

                if ($retryCount < 5) {
                    $this->logger("Retrying download", $logData);
                    $deferred->resolve($this->download($file->url));
                } else {
                    $this->updateStatus($file, File::STATUS_FAILED);
                    $deferred->reject(new \RuntimeException("Download failed after 5 retries"));
                }
            });

        $deferred->promise();
//        $loop->run();
    }

    // TODO: move from downloader
    private function updateStatus(File $file, int $status)
    {
        $file->status = $status;
        $file->save();
    }

    private function logger(string $message, array $data = []): void
    {
        Log::info($message, $data);
    }
}
