<?php

namespace App\Services\Identity;

use Illuminate\Support\Facades\Log;

/**
 * Stands in for the real NIRA TPI integration until credentials and PDPO
 * registration are in place — see docs/COMPLIANCE.md. Always "verifies"
 * so the rest of the registration flow can be built and tested now.
 */
class FakeNiraVerifier implements NiraVerifier
{
    public function verify(array $identity): bool
    {
        Log::info('FakeNiraVerifier: skipping real NIRA TPI call', $identity);

        return true;
    }
}
