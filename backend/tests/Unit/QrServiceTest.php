<?php

namespace Tests\Unit;

use App\Models\EventRegistration;
use App\Services\QrService;
use Tests\TestCase;

class QrServiceTest extends TestCase
{
    public function test_signature_is_deterministic_and_verifiable(): void
    {
        $service = new QrService();

        $registration = new EventRegistration();
        $registration->id = 42;
        $registration->registration_no = 'REG-2025-0042';

        $token = 'abc123token';

        $sig1 = $service->sign($token, $registration);
        $sig2 = $service->sign($token, $registration);

        $this->assertSame($sig1, $sig2, 'Signature must be deterministic');
        $this->assertTrue($service->verifySignature($token, $registration, $sig1));
        $this->assertFalse($service->verifySignature($token, $registration, 'tampered'));
    }

    public function test_payload_contains_token_and_registration_no(): void
    {
        $service = new QrService();
        $registration = new EventRegistration();
        $registration->id = 7;
        $registration->registration_no = 'REG-2025-0007';

        $payload = json_decode($service->payload('tok', $registration), true);

        $this->assertSame('tok', $payload['t']);
        $this->assertSame('REG-2025-0007', $payload['r']);
    }
}
