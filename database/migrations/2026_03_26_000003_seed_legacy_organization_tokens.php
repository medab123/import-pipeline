<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the legacy hardcoded tokens into the organization_tokens table.
     */
    public function up(): void
    {
        // Production org, fallback to first local org for dev/testing
        $organizationUuid = DB::table('organizations')
            ->where('uuid', '3959c02e-a0fd-4074-8925-3fae99e73a7f')
            ->value('uuid') ?? DB::table('organizations')->value('uuid');

        if (! $organizationUuid) {
            return;
        }

        $legacyTokens = [
            'org_mom05ONDS6WFt4rZldLvjAIeL4IF3znXqBxjXZRX' => 18,
            'org_HkOqcZA2GfvdYmCK5wn6S2MAeH5zzy8kpDX14OJx' => 19,
            'org_GiIf5q6LzValC6MdS8qdmUMXEp8uLXikicc1r5nj' => 20,
            'org_8laPkCftP5yz4Uac2BtZJl92RdIerQZ4Q7AEXRKB' => 21,
            'org_6aL1QGsRe17KCUZEvg8Ytlkh8G5fbtoZXttiGbZ7' => 22,
            'org_UYwrYFPKBrjGUhjGKskTS3LdaFPjF1YBcozKqvBU' => 26,
            'org_3CVleNTVogGjtDux2e4OeGCjG8Bg0TyNyiyRggbz' => 27,
            'org_mBSLD3v4Pe3sZ3OZxx4BaNfiwtO3PLapC9ANv8jI' => 30,
            'org_NECgWbp4cYT8wEgLMMlJipt54jAAYPLueQoucXUv' => 31,
            'org_oYuZjB3k9ucoGfcM3W87VariiYSnWWUy9DiJWOYs' => 35,
            'org_JTXWSOYtyL6xVMLpBYU4ABAldCMrv4QPZeobaOXU' => 36,
            'org_UL4RKTtB99xXPIxL6IZbLjbpsyZn79zjfYwIfC1M' => 16,
            'org_1XMC326GEWgaEtz4wkQJPHABMWL5Rlr9qkRcLWTn' => 15,
            'org_jxicsyqxOsX9ml0dCsei7csRAs1JkcZtMQH81KRy' => 13,
            'org_csM7oFD02G0Ox58BpwPkiCcqkNLqRRxBgGXHxa3n' => 11,
            'org_cjJ3EgULE2yZgRdwPeaVsXtALD2qSa68ouEHMCnd' => 10,
            'org_1MYGhMATT26S5tdGNNMp2UZCNM62qTLaVTWzCv6' => 6,
            'org_wOZ7vl92BwYM8LchxQq1nwboxKp4hv9Z6NP9Rxs' => 9,
            'org_QWAoUGvYYAXXjscCcTJIfraQoXq86B1dXz6iUTd8' => 8,
            'org_3F77FULX0xX1uNMoXR86wzJV99n5gta17NVwCAmy' => 4,
            'org_48L4C6XCjv2Nc2tKGJprvoIeQmawmVc1bKbQUvWw' => 5,
            'org_1NWJhNnqEIragnksl3UDVSx53zogvMHbdrtWORvA' => 1,
            'org_JbLkZYGxLGXnqD0flQR8Y7dhsyGxbYa9eRAA8Fs2' => 43,
            'org_m3SWdfQDoRUuRWOpNOqbL9YF8UMozemvm1K8IKEs' => 42,
            'org_Oo6K1a7feuyZVSnRj7bnPWvuUoyyxb67YqMuiSXo' => 41,
            'org_uEswVjTANJFnrPCfavQ6D2q9PjSC98uhooUiB4F1' => 40,
        ];

        foreach ($legacyTokens as $token => $pipelineId) {
            DB::table('organization_tokens')->insert([
                'organization_uuid' => $organizationUuid,
                'name' => 'Legacy Token (Pipeline #'.$pipelineId.')',
                'description' => 'Migrated from hardcoded middleware token',
                'token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('organization_tokens')
            ->where('name', 'like', 'Legacy Token (Pipeline #%)')
            ->delete();
    }
};
