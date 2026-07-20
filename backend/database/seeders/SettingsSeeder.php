<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // --- Site (public) ---
            ['key' => 'site.name', 'value' => 'Alumni Event Management', 'group' => 'site', 'is_public' => true],
            ['key' => 'site.logo', 'value' => null, 'group' => 'site', 'is_public' => true],
            ['key' => 'site.favicon', 'value' => null, 'group' => 'site', 'is_public' => true],
            ['key' => 'site.contact_email', 'value' => 'info@ams.test', 'group' => 'site', 'is_public' => true],

            // --- Theme (public) ---
            ['key' => 'theme.primary_color', 'value' => '#4f46e5', 'group' => 'theme', 'is_public' => true],
            ['key' => 'theme.mode', 'value' => 'light', 'group' => 'theme', 'is_public' => true],

            // --- Payment (secret) ---
            ['key' => 'payment.mode', 'value' => 'sandbox', 'group' => 'payment', 'is_public' => false],
            ['key' => 'payment.sslcommerz.store_id', 'value' => null, 'group' => 'payment', 'is_encrypted' => true],
            ['key' => 'payment.sslcommerz.store_password', 'value' => null, 'group' => 'payment', 'is_encrypted' => true],
            ['key' => 'payment.bkash.app_key', 'value' => null, 'group' => 'payment', 'is_encrypted' => true],
            ['key' => 'payment.bkash.app_secret', 'value' => null, 'group' => 'payment', 'is_encrypted' => true],
            ['key' => 'payment.nagad.merchant_id', 'value' => null, 'group' => 'payment', 'is_encrypted' => true],
            ['key' => 'payment.nagad.private_key', 'value' => null, 'group' => 'payment', 'is_encrypted' => true],

            // --- Email (secret) ---
            ['key' => 'email.host', 'value' => null, 'group' => 'email', 'is_encrypted' => false],
            ['key' => 'email.port', 'value' => '587', 'group' => 'email', 'is_encrypted' => false],
            ['key' => 'email.username', 'value' => null, 'group' => 'email', 'is_encrypted' => true],
            ['key' => 'email.password', 'value' => null, 'group' => 'email', 'is_encrypted' => true],
            ['key' => 'email.from_address', 'value' => 'no-reply@ams.test', 'group' => 'email', 'is_encrypted' => false],

            // --- SMS (secret) ---
            ['key' => 'sms.driver', 'value' => 'log', 'group' => 'sms', 'is_encrypted' => false],
            ['key' => 'sms.from', 'value' => 'AMS', 'group' => 'sms', 'is_encrypted' => false],
            ['key' => 'sms.api_token', 'value' => null, 'group' => 'sms', 'is_encrypted' => true],
        ];

        foreach ($defaults as $item) {
            $encrypted = (bool) ($item['is_encrypted'] ?? false);
            $value = $item['value'];

            Setting::firstOrCreate(
                ['key' => $item['key']],
                [
                    'group'        => $item['group'],
                    'is_public'    => (bool) ($item['is_public'] ?? false),
                    'is_encrypted' => $encrypted,
                    'value'        => ($encrypted && filled($value)) ? Setting::wrapEncrypted($value) : $value,
                ]
            );
        }
    }
}
