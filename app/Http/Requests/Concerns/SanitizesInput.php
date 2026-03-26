<?php

namespace App\Http\Requests\Concerns;

class SanitizesInput
{
    /**
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function forFields(array $fields, array $input): array
    {
        $sanitized = [];

        foreach ($fields as $field) {
            if (! static::keyExists($input, $field)) {
                continue;
            }

            data_set($sanitized, $field, static::sanitizeValue(data_get($input, $field)));
        }

        return $sanitized;
    }

    public static function normalizeEmail(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strtolower(trim($value));
    }

    private static function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(
                static fn (mixed $item): mixed => static::sanitizeValue($item),
                $value,
            );
        }

        if (! is_string($value)) {
            return $value;
        }

        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $value) ?? $value;

        if (config('security.sanitize.strip_tags', true)) {
            $sanitized = strip_tags($sanitized);
        }

        if (config('security.sanitize.collapse_whitespace', true)) {
            $sanitized = preg_replace('/[ \t]+/u', ' ', $sanitized) ?? $sanitized;
        }

        return trim($sanitized);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function keyExists(array $input, string $field): bool
    {
        $segments = explode('.', $field);
        $current = $input;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];
        }

        return true;
    }
}
