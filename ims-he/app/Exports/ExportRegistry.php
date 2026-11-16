<?php

namespace App\Exports;

class ExportRegistry
{
    public static function all(): array
    {
        return ExportDatasets::all();
    }

    public static function forRole(string $role): array
    {
        return array_values(array_filter(self::all(), fn (array $dataset): bool => in_array($role, $dataset['roles'], true)));
    }

    public static function find(string $key): ?array
    {
        foreach (self::all() as $dataset) {
            if ($dataset['key'] === $key) {
                return $dataset;
            }
        }

        return null;
    }
}
