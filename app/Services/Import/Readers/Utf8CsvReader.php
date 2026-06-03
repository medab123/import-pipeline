<?php

declare(strict_types=1);

namespace App\Services\Import\Readers;

use Elaitech\Import\Services\Core\Contracts\ReaderInterface;
use Elaitech\Import\Services\Reader\Implementations\CsvReader;

/**
 * CSV reader decorator that normalises source bytes to UTF-8 before parsing.
 *
 * Many dealer feeds (e.g. DealerCenter exports) are encoded in Windows-1252
 * rather than UTF-8. Those bytes survive every pipeline stage untouched and
 * only blow up at the save stage, where Eloquent json_encodes the result and
 * throws "Malformed UTF-8 characters, possibly incorrectly encoded.".
 * Converting the raw contents once here keeps every downstream consumer — map,
 * prepare, save and the Inertia preview — working with valid UTF-8.
 *
 * Wraps {@see CsvReader} (which is final) rather than extending it, delegating
 * all parsing and option handling so behaviour stays in lockstep with the package.
 */
final class Utf8CsvReader implements ReaderInterface
{
    public function __construct(private readonly CsvReader $reader = new CsvReader) {}

    public function read(string $contents, array $options = []): array
    {
        return $this->reader->read($this->toUtf8($contents), $options);
    }

    public function getType(): string
    {
        return $this->reader->getType();
    }

    public function getOptions(): array
    {
        return $this->reader->getOptions();
    }

    public function validateOptions(array $options): void
    {
        $this->reader->validateOptions($options);
    }

    private function toUtf8(string $contents): string
    {
        // Drop a UTF-8 byte-order mark so it can't leak into the first header.
        // Operates on raw bytes (no /u modifier) since $contents may not be valid UTF-8 yet.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if ($contents === '' || mb_check_encoding($contents, 'UTF-8')) {
            return $contents;
        }

        // Windows-1252 is a superset of ISO-8859-1 and the de-facto encoding for
        // Windows-based exports, so it is the safest assumption for non-UTF-8 input.
        return mb_convert_encoding($contents, 'UTF-8', 'Windows-1252') ?: $contents;
    }
}
