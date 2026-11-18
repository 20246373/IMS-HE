# IMS-HE
IMS-HE which stands for Information Management System for Hardware Enterprise. This repository contains all development progress of the project.

## How to run

You need PHP 8.3 or newer, [Composer](https://getcomposer.org/), and MySQL. XAMPP's MySQL or Laravel Herd's PHP both work.

1. **Start MySQL** (in the XAMPP Control Panel, click **Start** next to MySQL) and create an empty database named `ims_he`. You can do this in phpMyAdmin.
2. **Install and set up** (run these inside the `ims-he` folder):
   ```
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
3. **Point `.env` at MySQL.** Open `ims-he/.env` and set:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ims_he
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. **Create the tables and sample data, and link the product photo folder:**
   ```
   php artisan migrate --seed
   php artisan storage:link
   ```
5. **Start the app** and open http://localhost:8000:
   ```
   php artisan serve
   ```

### Sample accounts (password `password123`)

| Role | Email |
|---|---|
| Administrator | admin@example.com |
| President | president@example.com |
| Treasurer | treasurer@example.com |
| Inventory Clerk | clerk@example.com |
| Store Supervisor | supervisor@example.com |
| Employee | employee@example.com |
| Unassigned (waiting for a role) | newhire@example.com |
| Customer | customer1@example.com |

### Common problems

- **"No connection could be made because the target machine actively refused it"** means MySQL is not running. Start it in XAMPP.
- **"Unknown column ..." or "Table ... doesn't exist" after pulling** means a teammate added a database change. Run `php artisan migrate`.
- **Starting over with fresh sample data:** `php artisan migrate:fresh --seed`. This deletes everything in your local database.
- **Running the tests:** `php artisan test`. If you get "429 Too Many Requests" failures after several runs, run `php artisan cache:clear` and try again.

Angel: Inventory

Module: Inventory Management

1. Create the Category and Product models, controllers and views with add, edit, delete and list. Use is_active for hiding products instead of deleting them.

2. Add stock monitoring: a table showing stock_quantity, with a highlighted row when it is at or below reorder_level.

3. Create Inventory Service with two methods, deduct (productId, qty, referenceType, referenceId) and add (...). Each one updates stock_quantity and inserts a row in inventory_transactions (OUT or IN). Block anything that would make stock negative.

4. Push Inventory Service to main early, even with simple code, because Persons 3 and 4 depend on it.

5. Add the low-stock count to the dashboard.

all done :>
