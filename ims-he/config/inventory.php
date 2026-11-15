<?php

return [
    // How many times the reorder level to order when an automatic purchase order is raised.
    'auto_po_multiplier' => (int) env('INVENTORY_AUTO_PO_MULTIPLIER', 2),

    // Daily database backups created by the db:backup command.
    'backup_path' => env('INVENTORY_BACKUP_PATH', storage_path('app/backups')),
    'backup_keep' => (int) env('INVENTORY_BACKUP_KEEP', 14),
];
