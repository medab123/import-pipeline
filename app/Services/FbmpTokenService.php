<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Dealer;
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
     * Generate an FBMP app token for a dealer via the external API
     * and persist it on the dealer record.
     *
     * @param  string  $userEmail  Unique identifier for the dealer account (email format).
     * @param  int|null  $limitAccount  Account limit override.
     * @return string|null The generated token, or null on failure.
     */
    public function generateAndSave(Dealer $dealer, string $userEmail, ?int $limitAccount = null): ?string
    {
        $token = $this->generate($userEmail, $limitAccount);

        if (! $token) {
            return null;
        }

        $dealer->updateQuietly([
            'fbmp_app_access_token' => $token,
        ]);

        $dealer->resolveStatus();

        Log::info('FBMP token generated and saved for dealer.', [
            'dealer_id' => $dealer->id,
            'user_email' => $userEmail,
        ]);

        return $token;
    }

    /**
     * Regenerate the dealer's FBMP token via the external API and persist
     * the new value on the dealer record.
     *
     * @return string|null The new token, or null on failure.
     */
    public function regenerateAndSave(Dealer $dealer): ?string
    {
        if (empty($dealer->fbmp_app_access_token)) {
            Log::warning('Cannot regenerate FBMP token: dealer has no existing token.', [
                'dealer_id' => $dealer->id,
            ]);

            return null;
        }

        $newToken = $this->regenerate($dealer->fbmp_app_access_token);

        if (! $newToken) {
            return null;
        }

        $dealer->updateQuietly([
            'fbmp_app_access_token' => $newToken,
        ]);

        $dealer->resolveStatus();

        Log::info('FBMP token regenerated and saved for dealer.', [
            'dealer_id' => $dealer->id,
        ]);

        return $newToken;
    }

    /**
     * Revoke the dealer's FBMP token via the external API and clear it
     * from the dealer record.
     */
    public function revokeAndClear(Dealer $dealer): bool
    {
        if (empty($dealer->fbmp_app_access_token)) {
            return true;
        }

        $revoked = $this->delete($dealer->fbmp_app_access_token);

        if (! $revoked) {
            return false;
        }

        $dealer->updateQuietly([
            'fbmp_app_access_token' => null,
        ]);

        $dealer->resolveStatus();

        Log::info('FBMP token revoked and cleared for dealer.', [
            'dealer_id' => $dealer->id,
        ]);

        return true;
    }

    /**
     * Call the external API to generate a new FBMP token.
     *
     * @param  string  $userEmail  Unique identifier for the dealer account.
     * @param  int|null  $limitAccount  Account limit override.
     * @return string|null The generated token, or null on failure.
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
     *
     * @return string|null The new token, or null on failure.
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
