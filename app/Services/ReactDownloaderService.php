<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use Symfony\Component\HttpFoundation\Response;

class ReactDownloaderService implements DownloaderInterface
{
    private LoopInterface $loop;
    private Browser $http;
    private string $tmpDir;
    private string $completedDir;
    private int $maxRetries;

    public function __construct()
    {
        $this->loop = Loop::get();
        $timeouts = config('downloads.http');
        $connector = new Connector($this->loop, [
            'dns'     => true,
            'timeout' => $timeouts['tcp_timeout'] ?? 10.0,
        ]);

        $this->http = new Browser($this->loop, $connector);
        $this->http = $this->http
            ->withTimeout($timeouts['read_write_timeout'] ?? 30.0);

        $this->maxRetries = config('downloads.retries.max', 5);
    }

    public function download(iterable $files, int $retryCount = 0): void
    {
        $this->setStorage();
        $promises = [];

        foreach ($files as $file) {
            // TODO: Depends on requirements
            if ($file->status == File::STATUS_COMPLETED) {
                //                continue;
            }

            $promises[] = $this->getFile($file, $retryCount);
        }

        \React\Promise\all($promises)
            ->then(function (): void {
                $this->loop->stop();
            });
        //            ->otherwise(function (\Throwable $e): void {
        //                $this->loop->stop();
        //            });

        $this->loop->run();
    }

    public function setStorage(): void
    {
        $paths = config('downloads.paths');
        $this->tmpDir = $paths['tmp'];
        $this->completedDir = $paths['completed'];

        if (is_dir($this->tmpDir) === false) {
            @mkdir($this->tmpDir, 0775, true);
        }

        if (is_dir($this->completedDir) === false) {
            @mkdir($this->completedDir, 0775, true);
        }
    }

    private function getFile(File $file, int $retryCount = 0): PromiseInterface
    {
        $deferred = new Deferred();
        $fileName = $file->name;
        $tempFilePath = $this->tmpDir . DIRECTORY_SEPARATOR . $fileName . ".part";
        $completedFilePath = $this->completedDir . DIRECTORY_SEPARATOR . $fileName;
        $offset = file_exists($tempFilePath) ? filesize($tempFilePath) : 0;
        $headers = [];

        if ($offset > 0) {
            $headers['Range'] = "bytes=" . $offset . "-";
        }

        $request = $this->http->requestStreaming('GET', $file->url, $headers);

        $request
            ->then(function (ResponseInterface $response) use (
                $file,
                $deferred,
                $offset,
                $tempFilePath
            ) {
                $status = $response->getStatusCode();

                if ($status === Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE) {
                    Log::info("Range not satisfiable — likely already complete", [
                        'file' => $file->toArray(),
                    ]);

                    $this->finalizeIfComplete($file, $offset);
                    $deferred->resolve();

                    return;
                }

                if (in_array($status, [200, 206], true) === false) {
                    Log::error("Unsuccess response status", [
                        'file' => $file->toArray(),
                    ]);

                    $this->updateFileData($file, [
                        'status' => File::STATUS_FAILED,
                    ]);

                    $deferred->reject(new \RuntimeException("Unexpected HTTP " . $status));
                }

                $fileHandle = fopen($tempFilePath, 'c+b');
                if ($fileHandle === false) {
                    Log::error("Failed to open file for writing", [
                        'file' => $file->toArray(),
                    ]);

                    $this->updateFileData($file, [
                        'status' => File::STATUS_FAILED,
                    ]);

                    $deferred->reject(new \RuntimeException("Failed to open file for writing"));
                }

                $contentLength = $this->getContentLength($response);
                $body = $response->getBody();
                $bytes = $offset;

                Log::info("Download stream started", [
                    'file' => $file->toArray(),
                ]);

                $this->updateFileData($file, [
                    'status'    => File::STATUS_IN_PROGRESS,
                    'full_size' => $contentLength,
                ]);

                $body->on('data', function ($chunk) use (&$bytes, $fileHandle, $file): void {
                    $this->handleStreamData($chunk, $file, $fileHandle, $bytes);
                });

                $body->on('end', function () use ($fileHandle, $deferred, $file, $contentLength, &$bytes): void {
                    $this->handleStreamEnd($deferred, $file, $fileHandle, $contentLength, $bytes);
                });

                $body->on('error', function ($e) use ($deferred, $file, $fileHandle): void {
                    $this->handleStreamError($deferred, $file, $fileHandle, $e);
                });
            })
            ->otherwise(function (\Throwable $e) use ($deferred, $file, $retryCount): void {
                $this->retry($deferred, $file, $retryCount, $e);
            });

        return $deferred->promise();
    }

    private function handleStreamData(string $chunk, File $file, $fileHandle, int &$downloadedBytes): void
    {
        $chunkLength = mb_strlen($chunk);
        $bytesWritten = fwrite($fileHandle, $chunk);

        if ($bytesWritten === false) {
            throw new \RuntimeException('Write failed');
        }

        $downloadedBytes += $bytesWritten;

        // TODO: Depends on requirements. (Update spam)
        if ($downloadedBytes % (1024 * 1024) < $chunkLength) {
            $this->updateFileData($file, [
                'downloaded_size' => $downloadedBytes,
            ]);
        }
    }

    private function handleStreamEnd(
        Deferred $deferred,
        File     $file,
        $fileHandle,
        int      $contentRange,
        int      $bytes
    ): void {
        if (is_resource($fileHandle)) {
            fflush($fileHandle);
            fclose($fileHandle);
        }

        Log::info('Download completed', ['file' => $file->toArray()]);

        if ($contentRange !== null && $bytes < $contentRange) {
            Log::error("Stream ended early", [
                'file' => $file->toArray(),
            ]);

            $this->updateFileData($file, [
                'status' => File::STATUS_FAILED,
            ]);

            $deferred->reject(new \RuntimeException('Stream ended early'));
        }

        $this->finalizeIfComplete($file, $bytes, $contentRange);
        $deferred->resolve();
    }

    private function handleStreamError(
        Deferred   $deferred,
        File       $file,
        $fileHandle,
        \Throwable $error
    ): void {
        if (is_resource($fileHandle)) {
            fclose($fileHandle);
        }

        Log::error("Download stream error", [
            'file'  => $file->toArray(),
            'error' => $error->getMessage(),
        ]);

        $this->updateFileData($file, [
            'status' => File::STATUS_FAILED,
        ]);

        $deferred->reject($error);
    }

    private function retry(
        Deferred $deferred,
        File $file,
        int $retryCount,
        \Throwable $e
    ): void {
        if ($retryCount < $this->maxRetries) {
            Log::info('Download retry', [
                'file'  => $file->toArray(),
                'error' => $e->getMessage(),
            ]);

            // TODO: maybe add some delay for retry
            //            $delay = pow(2, $retryCount);
            //            $this->loop->addTimer($delay, function () use ($file, &$retryCount) {
            //                $this->download([$file], $retryCount + 1);
            //            });

            $this->download([$file], $retryCount + 1);
        } else {
            Log::error('Download failed', [
                'file'  => $file->toArray(),
                'error' => $e->getMessage(),
            ]);

            $this->updateFileData($file, [
                'status' => File::STATUS_FAILED,
            ]);

            $deferred->reject($e);
        }
    }

    private function finalizeIfComplete(File $file, int $bytesNow, ?int $expectedTotal = null): void
    {
        $tempFilePath = $this->tmpDir . DIRECTORY_SEPARATOR . $file->name . ".part";
        $completedFilePath = $this->completedDir . DIRECTORY_SEPARATOR . $file->name;
        $actual = file_exists($tempFilePath) ? (filesize($tempFilePath) ?: 0) : 0;
        $sizeToCompare = max($actual, $bytesNow);

        if ($expectedTotal !== null && $sizeToCompare < $expectedTotal) {
            // not complete yet
            return;
        }

        @rename($tempFilePath, $completedFilePath);

        $this->updateFileData($file, [
            'status'          => File::STATUS_COMPLETED,
            'downloaded_size' => $sizeToCompare,
        ]);
    }

    private function getContentLength(ResponseInterface $response): ?int
    {
        $contentRange = $response->getHeaderLine('Content-Range');

        if (preg_match('/bytes \d+-\d+\/(\d+)/', $contentRange, $matches)) {
            return (int)$matches[1];
        }

        $contentLength = (int)$response->getHeaderLine('Content-Length');

        return $contentLength > 0 ? $contentLength : PHP_INT_MAX;
    }

    // TODO: move from downloader
    private function updateFileData(File $file, array $data): void
    {
        try {
            foreach ($data as $key => $value) {
                $file->{$key} = $value;
            }

            $file->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
