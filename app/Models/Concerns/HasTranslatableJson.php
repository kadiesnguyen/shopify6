<?php

namespace App\Models\Concerns;

trait HasTranslatableJson
{
    public function translate(string $field, ?string $locale = null): ?string
    {
        $value = $this->{$field};

        if (! is_array($value)) {
            return is_string($value) ? $value : null;
        }

        $locale = $locale ?? app()->getLocale();

        return $value[$locale]
            ?? $value[config('app.fallback_locale', 'en')]
            ?? reset($value)
            ?: null;
    }
}
