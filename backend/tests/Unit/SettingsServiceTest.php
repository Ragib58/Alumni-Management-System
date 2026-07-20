<?php

namespace Tests\Unit;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_and_get_roundtrips_a_value(): void
    {
        $service = app(SettingsService::class);

        $service->set('site.name', 'My Alumni Portal', 'site');

        $this->assertSame('My Alumni Portal', $service->get('site.name'));
    }

    public function test_encrypted_values_are_stored_encrypted_but_read_back_plain(): void
    {
        $service = app(SettingsService::class);

        $service->set('payment.bkash.app_secret', 'super-secret', 'payment', encrypted: true);

        // Raw DB value must not equal the plaintext.
        $raw = \App\Models\Setting::where('key', 'payment.bkash.app_secret')->first();
        $this->assertNotSame('super-secret', json_encode($raw->value));

        // Service returns the decrypted value.
        $this->assertSame('super-secret', $service->get('payment.bkash.app_secret'));
    }

    public function test_public_settings_only_expose_public_keys(): void
    {
        $service = app(SettingsService::class);
        $service->set('site.name', 'Public Name', 'site');
        \App\Models\Setting::where('key', 'site.name')->update(['is_public' => true]);
        $service->flush();

        $service->set('payment.mode', 'live', 'payment');

        $public = $service->publicSettings();

        $this->assertArrayHasKey('site.name', $public);
        $this->assertArrayNotHasKey('payment.mode', $public);
    }
}
