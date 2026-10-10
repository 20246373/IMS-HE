# IMS-HE
IMS-HE which stands for Information Management System for Hardware Enterprise. This repository contains all development progress of the project.

Angel: Inventory

Module: Inventory Management

1. Create the Category and Product models, controllers and views with add, edit, delete and list. Use is_active for hiding products instead of deleting them.

2. Add stock monitoring: a table showing stock_quantity, with a highlighted row when it is at or below reorder_level.

3. Create Inventory Service with two methods, deduct (productId, qty, referenceType, referenceId) and add (...). Each one updates stock_quantity and inserts a row in inventory_transactions (OUT or IN). Block anything that would make stock negative.

4. Push Inventory Service to main early, even with simple code, because Persons 3 and 4 depend on it.

5. Add the low-stock count to the dashboard.

all done :>
