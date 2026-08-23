<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin wrapper around DGateway's REST API (https://dgateway.desispay.com/docs). Every call goes
 * through here so the API key and error handling live in one place.
 */
class DGatewayClient
{
    public function __construct(
        private readonly string $apiUrl,
        private readonly string $apiKey,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function post(string $path, array $body): array
    {
        $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
            ->timeout(30)
            ->post(rtrim($this->apiUrl, '/').'/'.ltrim($path, '/'), $body);

        if ($response->failed()) {
            Log::warning('DGateway request failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException(
                'DGateway request to '.$path.' failed with status '.$response->status().': '
                .($response->json('message') ?? $response->body())
            );
        }

        return $response->json();
    }
}
