<?php

namespace App\Support;

use App\Models\AuditLog as AuditLogModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Anyone can call this from any module:
 *
 *   use App\Support\AuditLog;
 *   AuditLog::record('product.deactivated', $product, 'Hidden from catalog');
 *
 * $target may be an Eloquent model (type + id are filled in), a plain string, or null.
 * The acting user is taken from the current session (null when run from CLI/seeders).
 */
class AuditLog
{
    public static function record(string $action, Model|string|null $target = null, ?string $details = null): AuditLogModel
    {
        return AuditLogModel::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'target_type' => $target instanceof Model ? class_basename($target) : $target,
            'target_id'   => $target instanceof Model ? (string) $target->getKey() : null,
            'details'     => $details,
        ]);
    }
}
