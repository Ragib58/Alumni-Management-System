<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Settings are read on almost every request — resolve once per lifecycle.
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        // Force HTTPS-generated URLs in production behind a proxy/load balancer.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Guard against slow/large lazy loads leaking to production.
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->applyDynamicSettings();
    }

    /**
     * Override runtime config from DB-backed settings (payment/mail/sms/site),
     * so admins can manage them without redeploys. Guarded so console commands
     * (migrate, etc.) run before the table exists.
     */
    private function applyDynamicSettings(): void
    {
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            // Skip during migrate/seed to avoid querying a not-yet-migrated table.
            return;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            /** @var SettingsService $settings */
            $settings = $this->app->make(SettingsService::class);
            $all = $settings->all();

            // Payment gateway keys.
            foreach (['sslcommerz', 'bkash', 'nagad'] as $gw) {
                foreach ($all as $key => $value) {
                    if (str_starts_with($key, "payment.$gw.") && filled($value)) {
                        config(['payment.gateways.'.$gw.'.'.substr($key, strlen("payment.$gw.")) => $value]);
                    }
                }
            }

            // Mail.
            if (filled($all['email.host'] ?? null)) {
                config([
                    'mail.mailers.smtp.host'     => $all['email.host'],
                    'mail.mailers.smtp.port'     => $all['email.port'] ?? config('mail.mailers.smtp.port'),
                    'mail.mailers.smtp.username' => $all['email.username'] ?? null,
                    'mail.mailers.smtp.password' => $all['email.password'] ?? null,
                    'mail.from.address'          => $all['email.from_address'] ?? config('mail.from.address'),
                    'mail.from.name'             => $all['site.name'] ?? config('mail.from.name'),
                ]);
            }

            // SMS.
            if (filled($all['sms.driver'] ?? null)) {
                config(['sms.driver' => $all['sms.driver'], 'sms.from' => $all['sms.from'] ?? config('sms.from')]);
            }

            // App name.
            if (filled($all['site.name'] ?? null)) {
                config(['app.name' => $all['site.name']]);
            }
        } catch (\Throwable) {
            // Never let settings hydration break the app boot.
        }
    }
}
