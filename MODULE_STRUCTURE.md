# IMS-H E — Module-Oriented Structure

This refactor separates route declarations by the requested business modules with role-based access control. Existing controllers, models, services, migrations, and Blade templates remain in their standard Laravel locations, so their namespaces and the UI are unchanged.

## Staff Roles and Module Access

### President (Admin)
- **User Account Management**: Creating, editing, deleting, unlocking accounts, and assigning roles
- **Financial Recording and Reporting**: Financial Statements, Cash Flow Reports, and Data Export
- **Report Generation**: Accessing comprehensive company reports
- **Inventory Management**: Monitoring stock levels, updating quantities, and setting reorder points
- **Procurement Management**: Managing purchase orders, recording supplier info, verifying receipts, and handling deliveries
- **Payroll Processing**: Managing employee records, logging/updating attendance, computing wages, and generating payslips
- **Sales Management**: Processing transactions via POS

### Treasurer
- **Payroll Processing**: Managing employee records, logging/updating attendance, computing wages, and generating payslips
- **Financial Recording and Reporting**: Recording transactions, income, and expenses
- **Report Generation**: Generating financial reports

### Inventory Clerk
- **Inventory Management**: Monitoring stock levels, updating quantities, and setting reorder points
- **Procurement Management**: Managing purchase orders, recording supplier info, verifying receipts, and handling deliveries

### Store Supervisor
- **Sales Management**: Processing transactions via POS
- **Inventory Management**: Updating stock
- **Procurement Management**: Recording procurement and verifying receipts
- **Attendance Management**: Viewing and updating records
- **Report Generation**: Generating sales and inventory reports

### Employee / Sales Associate
- **Sales Management**: Recording sales transactions, viewing product catalogs, processing customer records, and printing receipts

## Route map

- `routes/modules/staff-auth.php` — User Authentication & Session (staff username login/logout)
- `routes/modules/user-account-management.php` — User Account Management and audit-log routes (President-only)
- `routes/modules/financial-recording.php` — Financial Recording and Reporting (President and Treasurer)
- `routes/modules/inventory-management.php` — Inventory Management (President, Inventory Clerk, and Store Supervisor)
- `routes/modules/procurement-management.php` — Procurement Management (President, Inventory Clerk, and Store Supervisor)
- `routes/modules/payroll-processing.php` — Payroll Processing (President and Treasurer)
- `routes/modules/sales-management.php` — Sales Management and POS (President, Store Supervisor, and Employee)
- `routes/modules/report-generation.php` — Report Generation (President, Treasurer, and Store Supervisor)
- `routes/modules/catalog.php` — Product Catalog public landing/catalog/details pages
- `routes/modules/customer.php` — Customer Account and Cart routes using the customer guard
- `routes/web.php` — Route entry point and shared staff authentication/RBAC boundary

## Existing Laravel implementation locations

|| Module | Controllers | Models / services | Views |
||---|---|---|---|
|| User Authentication & Session | `AuthController` | `User`, `AuditLog` | `auth/` |
|| User Account Management | `UserController`, `AuditLogController` | `User`, `Employee`, `AuditLog` | `users/`, `audit/` |
|| Financial Recording and Reporting | `FinancialController` | `LedgerEntry` | `financial/` |
|| Inventory Management | `CategoryController`, `ProductController`, `StockController` | `Category`, `Product`, `InventoryTransaction`, `InventoryService` | `categories/`, `products/`, `stock/` |
|| Sales Management | `PosController`, `SaleController`, `SaleService` | `Invoice`, `InvoiceItem`, `Payment`, `InventoryService` | `pos/`, `sales/` |
|| Procurement Management | `SupplierController`, `PurchaseOrderController`, `DeliveryController` | `Supplier`, `PurchaseOrder`, `PurchaseOrderItem`, `ProcurementService`, `InventoryService` | `suppliers/`, `purchase-orders/`, `deliveries/` |
|| Payroll Processing | `AttendanceController`, `EmployeeController`, `PayrollController`, `PayrollService` | `Employee`, `Attendance`, `Payroll`, `Payslip` | `attendance/`, `employees/`, `payrolls/`, `payslips/` |
|| Report Generation | `ReportController` | Various models for aggregation | `reports/` |
|| Customer Account | `CustomerAuthController` | `Customer` and customer auth guard | `customer/` |
|| Product Catalog | `CatalogController` | `Product`, `Category` | `catalog/` |
|| Cart (without checkout) | `CartController` | `Cart`, `CartItem`, customer guard | `cart/` |

## Shared files intentionally kept in Laravel's conventional locations

- `app/Models/` — shared Eloquent models and relationships
- `app/Services/` — cross-module business services, especially `InventoryService`, `SaleService`, `PayrollService`
- `database/migrations/` — current schema migration
- `database/seeders/DatabaseSeeder.php` — sample staff, customer, categories, products, and suppliers
- `resources/views/layouts/app.blade.php` — shared layout (unchanged)

## Notes

- The Blade templates and CSS/UI files were not changed.
- `InventoryService` remains shared by sales and procurement; keep stock changes centralized there.
- The existing migration is a consolidated migration (`2026_10_08_000000_create_imshe_tables.php`) rather than 19 separate migration files. Its table creation order and foreign-key dependencies should be checked before splitting it into individual migrations.
- Role-based access control is implemented using the `role` middleware on each module's route group.
