# IMS-HE Team Guide

## Setup
```
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite        # .env uses DB_CONNECTION=sqlite
php artisan migrate:fresh --seed
php artisan serve
```
Seeded logins (password `password123`): `president`, `treasurer`, `clerk`, `supervisor`, `employee`.
**Dev-only accounts - change or remove before any real deployment.**

## Roles (stored in `users.role`)
`president`, `treasurer`, `inventory_clerk`, `store_supervisor`, `employee`, `customer`.
Use the constants in `App\Models\User` (e.g. `User::INVENTORY_CLERK`) rather than raw strings.

## RBAC middleware
Alias `role` (see `bootstrap/app.php`). Pass a comma-separated list of allowed roles:
```php
Route::middleware(['auth', 'role:inventory_clerk,store_supervisor'])->group(function () { ... });
Route::get('/accounts', ...)->middleware('role:president');
```
Anyone else gets a 403. In Blade: `@if(auth()->user()->hasRole('president','treasurer')) ... @endif`.

## InventoryService (the only code allowed to change stock)
```php
use App\Services\InventoryService;

app(InventoryService::class)->deduct($productId, $qty, 'Invoice', $invoice->id); // OUT
app(InventoryService::class)->add($productId, $qty, 'PO', $po->id);              // IN
```
Updates `products.stock_quantity`, writes `inventory_transactions`, and throws a
`ValidationException` if stock would go negative. Joins your `DB::transaction` if you have one open.
Never do `$product->decrement('stock_quantity')` directly.

## AuditLog helper
```php
use App\Support\AuditLog;

AuditLog::record('po.created', $purchaseOrder, 'PO for Sample Hardware Supply');
```
Args: action string, a model (or string / null), optional details. The acting user is picked up automatically.
Already logged: account create/update/delete/lock/unlock/password reset, staff login/logout, product hide/show.

## Other conventions
- Hide products with `is_active` (never delete). Use `Product::active()` in any customer/POS query.
- Customers log in through the same `/login` (their credentials live in `users`, profile in `customers`).
  Cart routes use `auth` + `role:customer`; guests are redirected to `/login` and returned afterwards.
- Sales invoices have `channel` = `In-store` (POS). The second half should use `Online` for web checkout.
- `purchase_order_items.quantity_received` supports partial deliveries; PO flips to `Delivered` when all lines are complete.
