<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class TestDownloaderViewModel extends ViewModel
{
    private readonly array $resultData;

    public function __construct(
        array $data
    ) {
        $this->resultData = $data;
    }

    /**
     * Create a TestDownloaderViewModel from DownloadResultData.
     */
    public static function fromDownloadResult(DownloadResultData $downloadResult): self
    {
        return new self(self::formatDownloadResultData($downloadResult));
    }

    /**
     * Create a TestDownloaderViewModel from array data (e.g., from session).
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Get the formatted test result data.
     */
    public function testResult(): array
    {
        return $this->getFormattedResult();
    }

    /**
     * Check if the test was successful.
     */
    public function isSuccess(): bool
    {
        return $this->resultData['success'] ?? false;
    }

    /**
     * Get the test message.
     */
    public function getMessage(): string
    {
        return $this->resultData['message'] ?? '';
    }

    /**
     * Get the test details.
     */
    public function getDetails(): ?array
    {
        return $this->resultData['details'] ?? null;
    }

    /**
     * Get the formatted result for display.
     */
    public function getFormattedResult(): array
    {
        return [
            'success' => $this->isSuccess(),
            'message' => $this->getMessage(),
            'details' => $this->getDetails(),
        ];
    }

    /**
     * Format DownloadResultData into array format.
     */
    private static function formatDownloadResultData(DownloadResultData $downloadResult): array
    {
        if (! $downloadResult->success) {
            return [
                'success' => false,
                'message' => 'Download test failed',
                'details' => [
                    'error' => 'Download was not successful',
                ],
            ];
        }

        $message = sprintf(
            'Download test successful! Downloaded %s bytes as "%s" (%s)',
            $downloadResult->fileSize ?? 'unknown',
            $downloadResult->filename ?? 'unknown file',
            $downloadResult->mimeType ?? 'unknown type'
        );

        return [
            'success' => true,
            'message' => $message,
            'details' => [
                'fileSize' => $downloadResult->fileSize,
                'filename' => $downloadResult->filename,
                'mimeType' => $downloadResult->mimeType,
                'contentLength' => $downloadResult->contents ? strlen($downloadResult->contents) : 0,
            ],
        ];
    }
}
