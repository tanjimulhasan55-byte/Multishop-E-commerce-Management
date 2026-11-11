<?php

namespace App\DTOs\Concerns;

trait CleansTranslations
{
    /**
     * Normalize a {locale: text} input: trim values, drop empty locales and
     * unsupported locales. Returns null when nothing remains.
     *
     * @param  array<string, mixed>|null  $value
     * @return array<string, string>|null
     */
    protected static function translations(?array $value): ?array
    {
        $clean = collect($value ?? [])
            ->only(config('app.supported_locales', ['en', 'bn']))
            ->map(fn ($v) => is_string($v) ? trim($v) : null)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();

        return $clean === [] ? null : $clean;
    }
}
