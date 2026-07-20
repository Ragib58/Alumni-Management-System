<?php

namespace App\Services;

use App\Models\EventRegistration;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generates unique, tamper-evident QR codes for registrations.
 *
 * Security model:
 *  - Each ticket gets a random, unique `qr_token` (uniqueness enforced by a DB
 *    unique index → duplication impossible).
 *  - The QR encodes a compact JSON payload: { t: token, r: registration_no }.
 *  - An HMAC `qr_signature` (keyed by APP_KEY) is stored so a scanned code can
 *    be verified server-side at check-in without trusting the client.
 */
class QrService
{
    /**
     * Create a brand-new, guaranteed-unique QR token.
     */
    public function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (\App\Models\Ticket::where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * Deterministic HMAC signature for a token + registration pair.
     */
    public function sign(string $token, EventRegistration $registration): string
    {
        return hash_hmac(
            'sha256',
            $token.'|'.$registration->id.'|'.$registration->registration_no,
            (string) config('app.key')
        );
    }

    public function verifySignature(string $token, EventRegistration $registration, string $signature): bool
    {
        return hash_equals($this->sign($token, $registration), $signature);
    }

    /**
     * The payload string embedded inside the QR image.
     */
    public function payload(string $token, EventRegistration $registration): string
    {
        return json_encode([
            't' => $token,
            'r' => $registration->registration_no,
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Render the QR code as an inline SVG string (no image extension required).
     */
    public function svg(string $token, EventRegistration $registration, int $size = 220): string
    {
        return (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($this->payload($token, $registration));
    }

    /**
     * Base64 data URI for embedding the SVG in HTML/PDF.
     */
    public function svgDataUri(string $token, EventRegistration $registration, int $size = 220): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($token, $registration, $size));
    }
}
