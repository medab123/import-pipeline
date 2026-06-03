<?php

declare(strict_types=1);

namespace Tests\Unit\Import\Readers;

use App\Services\Import\Readers\Utf8CsvReader;
use PHPUnit\Framework\TestCase;

class Utf8CsvReaderTest extends TestCase
{
    public function test_windows_1252_feed_is_parsed_and_json_encodable(): void
    {
        // 0xE9 = "é", 0x96 = en-dash "–", 0x99 = "™" in Windows-1252 — all invalid as bare UTF-8.
        $csv = "make,note\r\nCaf\xE9,2020\x96model \x99\r\n";

        // Reproduce the production failure mode against the raw bytes:
        // json_encode returns false on malformed UTF-8 (what blew up the save stage).
        $this->assertFalse(mb_check_encoding($csv, 'UTF-8'));
        $this->assertFalse(json_encode([$csv]));

        $rows = (new Utf8CsvReader)->read($csv);

        // The row must now survive json_encode (what Eloquent/Inertia do downstream).
        $json = json_encode($rows);
        $this->assertNotFalse($json);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());

        // Characters are converted, not stripped.
        $this->assertSame([['make' => 'Café', 'note' => '2020–model ™']], $rows);
    }

    public function test_valid_utf8_passes_through_unchanged(): void
    {
        $csv = "make,note\nÅäö,€uro\n";

        $rows = (new Utf8CsvReader)->read($csv);

        $this->assertSame([['make' => 'Åäö', 'note' => '€uro']], $rows);
    }

    public function test_utf8_bom_is_stripped_from_first_header(): void
    {
        $csv = "\xEF\xBB\xBFmake,model\nFord,F150\n";

        $rows = (new Utf8CsvReader)->read($csv);

        $this->assertSame([['make' => 'Ford', 'model' => 'F150']], $rows);
    }
}
