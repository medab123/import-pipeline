<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Dealer;
use App\Models\DealerFbmpToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class FbmpTokenService
{
    private string $baseUrl;

    private string $authToken;

    private int $defaultLimitAccount;

    public function __construct()
    {
        $this->baseUrl = config('scrap.fbmp.base_url');
        $this->authToken = config('scrap.fbmp.auth_token', '');
        $this->defaultLimitAccount = (int) config('scrap.fbmp.default_limit_account', 999);
    }

    /**
     * Generate a new FBMP token for a dealer via the external API and persist
     * it as a new DealerFbmpToken row.
     *
     * @return DealerFbmpToken|null The persisted token, or null on failure.
     */
    public function generateForDealer(Dealer $dealer, string $userEmail, ?int $limitAccount = null): ?DealerFbmpToken
    {
        $limit = $limitAccount ?? $this->defaultLimitAccount;

        $token = $this->generate($userEmail, $limit);

        if (! $token) {
            return null;
        }

        $row = $dealer->fbmpTokens()->create([
            'organization_uuid' => $dealer->organization_uuid,
            'token' => $token,
            'user_email' => $userEmail,
            'limit_account' => $limit,
        ]);

        $dealer->resolveStatus();

        Log::info('FBMP token generated and saved for dealer.', [
            'dealer_id' => $dealer->id,
            'token_id' => $row->id,
            'user_email' => $userEmail,
        ]);

        return $row;
    }

    /**
     * Regenerate the given token via the external API and update the row in place.
     */
    public function regenerateToken(DealerFbmpToken $token): bool
    {
        $newToken = $this->regenerate($token->token);

        if (! $newToken) {
            return false;
        }

        $token->update(['token' => $newToken]);
        $token->dealer?->resolveStatus();

        Log::info('FBMP token regenerated.', [
            'dealer_id' => $token->dealer_id,
            'token_id' => $token->id,
        ]);

        return true;
    }

    /**
     * Revoke the given token via the external API and delete the row.
     */
    public function revokeToken(DealerFbmpToken $token): bool
    {
        $revoked = $this->delete($token->token);

        if (! $revoked) {
            return false;
        }

        $dealer = $token->dealer;
        $token->delete();
        $dealer?->resolveStatus();

        Log::info('FBMP token revoked and deleted.', [
            'dealer_id' => $token->dealer_id,
            'token_id' => $token->id,
        ]);

        return true;
    }

    /**
     * Call the external API to generate a new FBMP token.
     */
    public function generate(string $userEmail, ?int $limitAccount = null): ?string
    {
        if (empty($this->authToken)) {
            Log::error('FBMP API auth token is not configured. Set FBMP_API_AUTH_TOKEN in .env.');

            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-auth-token' => $this->authToken,
            ])->get($this->baseUrl.'/generate', [
                'user' => $userEmail,
                'limitAccount' => $limitAccount ?? $this->defaultLimitAccount,
            ]);

            if ($response->failed()) {
                Log::error('FBMP token API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_email' => $userEmail,
                ]);

                return null;
            }

            $token = $response->json('token');

            if (empty($token)) {
                Log::error('FBMP token API returned empty token.', [
                    'body' => $response->body(),
                    'user_email' => $userEmail,
                ]);

                return null;
            }

            return $token;
        } catch (ConnectionException $e) {
            Log::error('FBMP token API connection failed.', [
                'error' => $e->getMessage(),
                'user_email' => $userEmail,
            ]);

            return null;
        }
    }

    /**
     * Call the external API to regenerate an existing FBMP token.
     */
    public function regenerate(string $oldToken): ?string
    {
        if (empty($this->authToken)) {
            Log::error('FBMP API auth token is not configured. Set FBMP_API_AUTH_TOKEN in .env.');

            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-auth-token' => $this->authToken,
            ])->get($this->baseUrl.'/regenerate', [
                'oldToken' => $oldToken,
            ]);

            if ($response->failed()) {
                Log::error('FBMP token regenerate API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $token = $response->json('token');

            if (empty($token)) {
                Log::error('FBMP token regenerate API returned empty token.', [
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $token;
        } catch (ConnectionException $e) {
            Log::error('FBMP token regenerate API connection failed.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Call the external API to revoke (delete) an FBMP token.
     */
    public function delete(string $token): bool
    {
        if (empty($this->authToken)) {
            Log::error('FBMP API auth token is not configured. Set FBMP_API_AUTH_TOKEN in .env.');

            return false;
        }

        try {
            $response = Http::withHeaders([
                'x-auth-token' => $this->authToken,
            ])->get($this->baseUrl.'/delete', [
                'token' => $token,
            ]);

            if ($response->failed()) {
                Log::error('FBMP token delete API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (ConnectionException $e) {
            Log::error('FBMP token delete API connection failed.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
