# AGENTS.md

## Project Overview
PHP manufacturing management system. Custom MVC framework, no Composer, no npm. Runs on XAMPP with MySQL.

## Stack
- PHP (no framework), Bootstrap 5.3, MySQL (`manufacturing_mgmt` DB)
- XAMPP: Apache + MySQL, localhost
- No build step, no package manager, no linter, no tests

## Routing
`index.php?controller=<module>&action=<method>` — all requests go through `index.php`.
- Default: `controller=auth`, `action=login`
- Controller map: `auth` → `/auth/`, `warehouse` → `/warehouse/`, `production` → `/production/`, `finance` → `/finance/`, everything else → `/admin/`
- Autoloader maps `App\Controllers\{Module}Controller` to `/{module}/controllers/`

## Architecture
```
core/          - Config (session, BASE_PATH), BaseModel (PDO connection)
app/helpers/   - Pagination helper
admin/         - Admin module (customers, items, POs, delivered)
auth/          - Login/signup (no layout wrapper, standalone views)
warehouse/     - PO creation, deliveries
production/    - Production tracking, history
finance/       - Finance dashboard, delivery receipts, file uploads
public/        - Static assets (Bootstrap, CSS, JS, fonts, images)
sql/           - Schema: schema.sql (main), finance_delivery_receipts.sql
uploads/       - Receipt files (finance module)
```

## Conventions
- Each module: `controllers/`, `models/`, `views/`, `views/layouts/main.php`
- Models extend `App\Core\BaseModel`, use `self::getConnection()` for PDO
- Soft deletes: `remove = 1` column, never hard delete
- Session flash messages: `$_SESSION['success']`, `$_SESSION['error']`
- Controllers use `render()` method that extracts data, captures view output, wraps in layout
- AuthController is the exception: renders views directly without layout wrapper
- Department-based access control checked in controller constructors

## Database
- DB: `manufacturing_mgmt`, user: `root`, no password
- Schema: `sql/schema.sql` + `sql/finance_delivery_receipts.sql`
- Install admin: `php install-admin.php` (creates admin/admin user)
- Key tables: users, customers, items, purchase_orders, purchase_order_items, deliveries, manufacturing_requests, sales_orders, delivery_receipts, production_history

## Key Gotchas
- Hardcoded absolute paths in autoloader (`C:\xampp\htdocs\order-billing-system\...`) — won't work outside XAMPP default install
- `URL_ROOT` hardcoded to `http://localhost/order-billing-system/`
- `WarehouseModel` has methods spanning POs, deliveries, production, and users — it's a god model
- `produced_quantity`, `delivered_quantity`, `delivery_quantity` columns referenced in models but not in `sql/schema.sql` — schema may be out of sync
- No CSRF protection on forms
- File uploads go to `uploads/receipts/` (finance module only)
- `warehouse` and `finance` controllers restrict actions by `$_SESSION['department']`

## Development
- No linting, no typecheck, no tests, no build commands
- Edit PHP files directly, refresh browser
- Bootstrap/Icons CSS/JS must be downloaded manually (see `public/README.txt`)
