<?php

namespace App\Services\Identity;

interface NiraVerifier
{
    /**
     * Verify a NIN against NIRA's Third Party Interface.
     *
     * @param  array{nin: string, card_number: ?string, dob: ?string}  $identity
     */
    public function verify(array $identity): bool;
}
