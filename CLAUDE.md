# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

Two unrelated trees share this workspace — they are not a single project.

- [vidrios-inventory/](vidrios-inventory/) — a PHP 8 + MySQL 8 glass-shop inventory app ("Vitralia"). Hand-rolled MVC, no framework, no package manager, no test suite. This is the working project.
- [.agents/skills/](.agents/skills/) + [skills-lock.json](skills-lock.json) — vendored skill bundles pinned from `anthropics/skills`. Treat as third-party content; see "Vendored skills" below.

There is no git history and no root-level build/lint/test tooling — do not invent commands.

## vidrios-inventory — architecture

### Request flow

1. Apache reads [vidrios-inventory/.htaccess](vidrios-inventory/.htaccess), blocks direct access to `app|core|config|database`, and rewrites everything else to `public/index.php?url=<path>`.
2. [public/index.php](vidrios-inventory/public/index.php) `session_start()`s, defines `ROOT`, manually requires the five `core/` classes (`Database`, `Model`, `Controller`, `Router`, `Paginator`), then registers a `spl_autoload_register` that resolves classes from `app/controllers` and `app/models`. Core classes are **not** autoloaded — they must be loaded up front. [core/Paginator.php](vidrios-inventory/core/Paginator.php) is the shared list-pagination helper (offset/limit + rendered `« 1 2 3 »` nav with filter-preserving querystrings); use it instead of hand-rolling pagination on new index views.
3. [core/Router.php](vidrios-inventory/core/Router.php) parses `/<segment>/<segment>/<params...>`, converts the first segment to Studly-case + `Controller` suffix and the second to camelCase action. Default route is `DashboardController@index`. All remaining segments are passed as positional arguments to the action via `call_user_func_array`.
4. Controllers extend [core/Controller.php](vidrios-inventory/core/Controller.php), which provides `render()`, `redirect()`, session/role helpers (`requireAuth`, `requireAdmin`), and `setFlash()`. `render()` wraps the view in `layouts/header` + `layouts/sidebar` + `layouts/footer` unless called with `withLayout: false`. It always exposes `$usuario`, `$stockBajoCount`, and `$flash` to the view and `extract()`s the supplied data array.
5. Models extend [app/models/BaseModel.php](vidrios-inventory/app/models/BaseModel.php) → [core/Model.php](vidrios-inventory/core/Model.php), which wraps the PDO singleton from [core/Database.php](vidrios-inventory/core/Database.php). All queries use prepared statements and backticked identifiers; columns are bound with `pdoType()` for correct `PDO::PARAM_*`.

### Domain invariants

- **Stock changes must go through [Movimiento::registrar()](vidrios-inventory/app/models/Movimiento.php)**, not direct `UPDATE productos SET stock_actual`. It opens a transaction, does `SELECT ... FOR UPDATE` on the product row, validates, updates `productos.stock_actual`, and inserts the audit row into `movimientos` atomically. `tipo='ajuste'` sets an **absolute** new stock; `entrada`/`salida` apply a delta. Salidas that exceed current stock throw.
- **Auth is session-based** with two roles (`admin`, `operador`) stored on `$_SESSION['usuario']`. `AuthController::login` regenerates the session id on success. `requireAdmin()` in the base controller is the gate for write operations on catalogs; routes for movimientos/reportes only need `requireAuth()`.
- **"Stock bajo" badge** in the sidebar comes from `Controller::contarStockBajo()`, which runs `COUNT(*) WHERE estado = 1 AND stock_actual <= stock_minimo` on every authenticated render. Keep that query cheap.
- **Schema is in [vidrios-inventory/database/schema.sql](vidrios-inventory/database/schema.sql)**, but it has drifted from the model layer. The SQL only declares five tables (`usuarios`, `categorias`, `proveedores`, `productos`, `movimientos`) with FKs `productos → categorias|proveedores` (ON DELETE SET NULL), `movimientos → productos` (CASCADE), `movimientos → usuarios|proveedores` (SET NULL). Meanwhile the codebase has live models for `Rol`/`Permiso` (RBAC: `roles`, `permisos`, `roles_permisos`), `Auditoria` (`auditorias`/`auditoria` log), `Pedido`, and the full geography stack (`paises`, `departamentos`, `provincias`, `distritos`, `ciudades`) — none of these tables are in `schema.sql`. Treat `schema.sql` as a partial baseline; before running new code that touches those models, confirm the tables actually exist in the target DB and add `CREATE TABLE` migrations if needed. The admin user is **not** seeded in the SQL — run the installer (below) after loading the schema.
- **Auth check is still session-string-based**, even though `Rol`/`Permiso` models exist: `Controller::isAdmin()` literally compares `$_SESSION['usuario']['rol']` to the string `'admin'`. RBAC tables are wired into models but not into the request gate. Don't switch new code to permission-codes without also updating the auth helpers.
- **Auditoría hook**: `Controller::audit($accion, $entidad, $entidadId, $descripcion)` proxies to [Auditoria::registrar()](vidrios-inventory/app/models/Auditoria.php) and swallows any throwable so logging never breaks the request flow. Call it after successful writes (create/update/delete on catalog or movimientos), not inside the same DB transaction.

### Install / run

```bash
# 1. Load schema (creates DB + tables + seed data for categorías/proveedores/productos/movimientos)
mysql -u root < vidrios-inventory/database/schema.sql

# 2. Seed the demo admin (email: admin@vitralia.co / password: vidrio123) — CLI-only script, refuses to run over HTTP
php vidrios-inventory/database/install.php

# 3. Serve. Production target is Apache + mod_rewrite (the .htaccess is required). The built-in PHP server ignores .htaccess, so routes will 404 under `php -S` unless you front it with a router script.
```

DB credentials live in [vidrios-inventory/config/database.php](vidrios-inventory/config/database.php) (defaults: `127.0.0.1:3306`, db `vidrios_inventory`, user `root`, empty password, utf8mb4). App-wide constants (`APP_NAME`, `UPLOAD_DIR`, `DEBUG`, timezone `America/Bogota`) live in [vidrios-inventory/config/config.php](vidrios-inventory/config/config.php). `DEBUG=true` prints full stack traces from the router's catch-all; flip it off for production. `BASE_URL` is set to `/proyecto/vidrios-inventory`; `Controller::redirect()` prepends it to any path that starts with `/`, so redirect targets must be relative to that prefix. `STOCK_CRITICO_DEFAULT=5` is the global low-stock fallback if `stock_minimo` is not set on the product.

### Conventions that are not obvious from file names

- All PHP files use `declare(strict_types=1);`. Concrete controllers and models are `final`; the base `Controller` is `abstract`. Keep that pattern.
- Identifiers, UI copy, flash messages, and comments are in Spanish. Match the existing tone when adding new screens.
- Product images are stored at `public/img/productos/` (`UPLOAD_DIR` / `UPLOAD_URL` from config). The `.htaccess` blocks `app|core|config|database` but **not** `public/`, so anything under `public/` is web-reachable.

## Vendored skills

`.agents/skills/*` is **vendored third-party content** installed from [skills-lock.json](skills-lock.json) (each skill pinned by `source` repo with `computedHash` integrity). Do not patch bugs or add features in-tree — changes will be overwritten on the next lockfile resync. Fixes belong upstream in the `source` repo named in the lockfile, or in a new skill authored outside the vendored tree. Edit `skills-lock.json` only via the tooling that produced it; manual edits desync the hashes.

Currently vendored (check the lockfile for the canonical list and source repos):
- `frontend-design` (`anthropics/skills`) — guidance for distinctive frontend UIs.
- `skill-creator` (`anthropics/skills`) — Python tooling for authoring/evaluating skills (`scripts/`, `agents/`, `eval-viewer/`). Confirm a Python interpreter is available before invoking; this repo declares none.
- `mysql` (`planetscale/database-skills`) — MySQL operations references (indexes, isolation, deadlocks, EXPLAIN, etc.).
- `php-mcp-server-generator` (`github/awesome-copilot`) — generator skill for PHP-based MCP servers.

## Platform notes

- Working directory is `D:\laragon\www\Alex ayuda\proyecto` on Windows (Laragon's docroot), but the shell is bash — use Unix syntax (`/dev/null`, forward slashes) in commands.
- The vidrios-inventory app assumes Apache with `mod_rewrite` and `mod_headers`; XAMPP/Laragon/WAMP all work. The PHP built-in server does not read `.htaccess`.
