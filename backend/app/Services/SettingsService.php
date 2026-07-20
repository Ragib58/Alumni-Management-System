<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Central key/value settings store with caching. Sensitive values (gateway
 * secrets, SMTP passwords) are transparently encrypted at rest.
 */
class SettingsService
{
    private const CACHE_KEY = 'app.settings.all';

    /**
     * All settings keyed by key => decrypted value (cached).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::all()->mapWithKeys(fn (Setting $s) => [
                $s->key => $s->getDecryptedValue(),
            ])->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Settings grouped by their `group`, with metadata — for the admin UI.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function grouped(bool $includeSecret = true): array
    {
        return Setting::orderBy('group')->get()
            ->groupBy('group')
            ->map(fn ($items) => $items->map(function (Setting $s) use ($includeSecret) {
                $value = $s->getDecryptedValue();
                // Mask secrets in the admin payload (show only whether set).
                if ($s->is_encrypted && ! $includeSecret) {
                    $value = filled($value) ? '••••••••' : null;
                }

                return [
                    'key'          => $s->key,
                    'value'        => $value,
                    'group'        => $s->group,
                    'is_encrypted' => $s->is_encrypted,
                    'is_public'    => $s->is_public,
                ];
            })->values()->all())
            ->all();
    }

    /**
     * Public settings safe to expose to the SPA (site name, logo, theme…).
     *
     * @return array<string, mixed>
     */
    public function publicSettings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY.'.public', function () {
            return Setting::where('is_public', true)->get()
                ->mapWithKeys(fn (Setting $s) => [$s->key => $s->getDecryptedValue()])
                ->all();
        });
    }

    public function set(string $key, mixed $value, ?string $group = null, ?bool $encrypted = null): Setting
    {
        $setting = Setting::firstOrNew(['key' => $key]);

        $encrypted ??= $setting->is_encrypted;

        $setting->group = $group ?? $setting->group ?? 'site';
        $setting->is_encrypted = (bool) $encrypted;
        $setting->value = $encrypted ? Setting::wrapEncrypted($value) : $value;
        $setting->save();

        $this->flush();

        return $setting;
    }

    /**
     * Bulk update from the admin form: [ ['key'=>..,'value'=>..], ... ].
     *
     * @param array<int, array{key:string, value:mixed}> $items
     */
    public function bulkUpdate(array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['key'])) {
                continue;
            }
            $existing = Setting::where('key', $item['key'])->first();
            if (! $existing) {
                continue; // only update known settings (created via seeder)
            }
            // Skip masked secrets (unchanged).
            if ($existing->is_encrypted && ($item['value'] === '••••••••' || $item['value'] === null)) {
                continue;
            }
            $this->set($item['key'], $item['value']);
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'.public');
    }
}
