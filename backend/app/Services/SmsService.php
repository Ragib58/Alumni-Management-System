<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin SMS gateway abstraction. Defaults to the "log" driver so the app works
 * out of the box; swap SMS_DRIVER + credentials to go live.
 */
class SmsService
{
    public function send(string $to, string $message, ?string $from = null): bool
    {
        $driver = config('sms.driver', 'log');
        $from ??= config('sms.from');

        return match ($driver) {
            'twilio' => $this->viaTwilio($to, $message, $from),
            'vonage' => $this->viaVonage($to, $message, $from),
            'http'   => $this->viaHttp($to, $message, $from),
            default  => $this->viaLog($to, $message, $from),
        };
    }

    private function viaLog(string $to, string $message, ?string $from): bool
    {
        Log::channel(config('logging.default'))->info('[SMS]', compact('to', 'from', 'message'));

        return true;
    }

    private function viaTwilio(string $to, string $message, ?string $from): bool
    {
        $cfg = config('sms.providers.twilio');

        $response = Http::asForm()
            ->withBasicAuth($cfg['sid'], $cfg['token'])
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$cfg['sid']}/Messages.json", [
                'To'   => $to,
                'From' => $cfg['from'] ?? $from,
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            Log::warning('Twilio SMS failed', ['to' => $to, 'body' => $response->body()]);
        }

        return $response->successful();
    }

    private function viaVonage(string $to, string $message, ?string $from): bool
    {
        $cfg = config('sms.providers.vonage');

        $response = Http::asForm()->post('https://rest.nexmo.com/sms/json', [
            'api_key'    => $cfg['key'],
            'api_secret' => $cfg['secret'],
            'to'         => $to,
            'from'       => $from,
            'text'       => $message,
        ]);

        return $response->successful();
    }

    private function viaHttp(string $to, string $message, ?string $from): bool
    {
        $cfg = config('sms.providers.http');

        if (empty($cfg['url'])) {
            return $this->viaLog($to, $message, $from);
        }

        $response = Http::withToken((string) $cfg['token'])->post($cfg['url'], [
            'to'      => $to,
            'from'    => $from,
            'message' => $message,
        ]);

        return $response->successful();
    }
}
