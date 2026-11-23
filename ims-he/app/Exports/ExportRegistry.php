<?php

namespace App\Exports;

use App\Models\User;

class ExportRegistry
{
    public static function all(): array
    {
        return ExportDatasets::all();
    }

    /** The datasets this account may export: one export permission per dataset. The Administrator has none (account management only). */
    public static function forUser(User $user): array
    {
        return array_values(array_filter(self::all(), fn (array $dataset): bool => self::allows($user, $dataset)));
    }

    public static function allows(User $user, array $dataset): bool
    {
        return $user->hasPermission($dataset['permission']);
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
