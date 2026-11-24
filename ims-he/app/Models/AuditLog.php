<?php

namespace App\Models;

use App\Exports\ExportRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $primaryKey = 'log_id';

    public $timestamps = false;              // logged_at is filled by the database default

    protected $guarded = [];

    protected function casts(): array
    {
        return ['logged_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** What each table's records are called on the Audit Log page. */
    private const RECORD_NAMES = [
        'users' => 'employee account',
        'customers' => 'customer account',
        'employees' => 'employee record',
        'attendance' => 'attendance',
        'payrolls' => 'payroll',
        'invoices' => 'online order',
        'purchase_orders' => 'purchase order',
        'products' => 'product',
        'suppliers' => 'supplier',
        'roles' => 'role',
    ];

    /** The customer who did the action (empty when an employee or the system did it). */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    /** "Type of user" filter values: who did the action. */
    public const ACTOR_TYPES = ['employees', 'customers'];

    /** Who did it, for the Name column: the employee's name, the customer's name or email, or "System". */
    public function actorName(): string
    {
        if ($this->user) {
            return $this->user->displayName();
        }

        if ($this->customer) {
            return trim($this->customer->first_name.' '.$this->customer->last_name) ?: $this->customer->email;
        }

        return 'System';
    }

    /** Actions that already say what happened, without naming the record. */
    private const PLAIN_ACTIONS = [
        'Login|users' => 'Logged in',
        'Logout|users' => 'Logged out',
        'Registered|users' => 'Signed up as an employee',
        'Login|customers' => 'Logged in',
        'Logout|customers' => 'Logged out',
        'Registered|customers' => 'Signed up as a customer',
        'Password Changed|users' => 'Replaced the default password',
        'Clocked In|attendance' => 'Clocked in',
        'Clocked Out|attendance' => 'Clocked out',
        'Backed up|database' => 'Backed up the database',
        'Exported|database' => 'Downloaded a database backup',
        'Exported|all' => 'Exported all data (ZIP)',
        'Exported|users' => 'Exported employee accounts',
        'Exported|customer_accounts' => 'Exported customer accounts',
        'Exported|audit_logs' => 'Exported the audit log',
    ];

    /**
     * The action in words, saying what it applied to, e.g. "Completed online order" or "Updated customer account".
     * Used for the Action filter and column (the record number is added by describe()).
     */
    public static function label(string $action, string $table): string
    {
        if (isset(self::PLAIN_ACTIONS[$action.'|'.$table])) {
            return self::PLAIN_ACTIONS[$action.'|'.$table];
        }

        // Actions whose name does not read well in front of the record ("Role Assigned employee account").
        $verb = match ($action) {
            'Role Assigned' => 'Assigned a role to',
            'Password Reset' => 'Reset the password of',
            'Resigned' => 'Recorded the resignation of',
            default => null,
        };
        if ($verb !== null) {
            return $verb.' '.(self::RECORD_NAMES[$table] ?? str_replace('_', ' ', $table));
        }

        if ($action === 'Exported') {   // Export Center downloads are logged with the dataset key
            $dataset = ExportRegistry::find($table);

            return 'Exported '.mb_strtolower($dataset['label'] ?? str_replace('_', ' ', $table));
        }

        return $action.' '.(self::RECORD_NAMES[$table] ?? str_replace('_', ' ', $table));
    }

    /** This entry in words with its record number, e.g. "Completed online order #12". */
    public function describe(): string
    {
        $label = self::label($this->action, $this->table_name);
        $named = isset(self::RECORD_NAMES[$this->table_name]) && ! isset(self::PLAIN_ACTIONS[$this->action.'|'.$this->table_name]);

        return $named && $this->record_id ? $label.' #'.$this->record_id : $label;
    }

    /**
     * One-line audit helper for every module:
     *   AuditLog::record('Created', 'products', $product->product_id);
     * Pass $userId when nobody is logged in yet (e.g. failed login / lockout).
     */
    public static function record(string $action, string $table, ?int $recordId = null, ?int $userId = null): self
    {
        return static::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
        ]);
    }

    /** Same as record(), for an action a customer did themselves (sign up, log in, place an order, ...). */
    public static function recordByCustomer(string $action, string $table, ?int $recordId, int $customerId): self
    {
        return static::create([
            'user_id' => null,
            'customer_id' => $customerId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
        ]);
    }
}
