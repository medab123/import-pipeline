<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Providers;

use Elaitech\Import\Services\Downloader\Contracts\DownloaderFactoryInterface;
use Elaitech\Import\Services\Downloader\Factories\DownloaderFactory;
use Elaitech\Import\Services\Downloader\Implementations\FtpDownloader;
use Elaitech\Import\Services\Downloader\Implementations\HttpDownloader;
use Elaitech\Import\Services\Downloader\Implementations\SftpDownloader;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final class DownloaderServiceProvider extends BaseImportServiceProvider
{
    protected function getServiceMappings(): array
    {
        return [
            'http' => HttpDownloader::class,
            'https' => HttpDownloader::class,
            'ftp' => FtpDownloader::class,
            'sftp' => SftpDownloader::class,
        ];
    }

    protected function getFactoryClass(): string
    {
        return DownloaderFactory::class;
    }

    protected function getFactoryInterface(): string
    {
        return DownloaderFactoryInterface::class;
    }

    protected function getServiceType(): string
    {
        return 'downloader';
    }

    protected function registerCustomServices(): void
    {
        // Register HTTP downloader with dependencies
        $this->app->singleton(HttpDownloader::class, function ($app) {
            return new HttpDownloader(
                http: $app->make(HttpClient::class),
                logger: $app->make(LoggerInterface::class)
            );
        });

        // Register other downloaders
        foreach (['ftp' => FtpDownloader::class, 'sftp' => SftpDownloader::class] as $type => $downloader) {
            $this->app->singleton($downloader, function ($app) use ($downloader) {
                return new $downloader(
                    logger: $app->make(LoggerInterface::class)
                );
            });
        }
    }
}
