# PHP Generic Database — Connection Wizard

> 🇧🇷 [Leia em Português (PT-BR)](README.pt-BR.md)

A browser-based assistant for configuring, testing and executing queries against any database supported by **PHP Generic Database**. Supports multiple engines (Native, PDO, ODBC, PDO+ODBC, Flat Files), bilingual UI (English / Portuguese) and a full SQL-to-QueryBuilder converter.

---

## Table of Contents

1. [Overview](#overview)
2. [Directory Structure](#directory-structure)
3. [Features](#features)
4. [Wizard Steps](#wizard-steps)
5. [Connection Management](#connection-management)
6. [Query Executor (Step 2)](#query-executor-step-2)
7. [Query Builder (Step 3)](#query-builder-step-3)
8. [Settings](#settings)
9. [Data Files](#data-files)
10. [Path Constants](#path-constants)
11. [Internationalisation](#internationalisation)
12. [API Reference](#api-reference)
13. [Extending the Wizard](#extending-the-wizard)

---

## Overview

The Wizard is a self-contained PHP application located in `wizard/`. It does **not** require a framework — only PHP ≥ 8.0 and the PHP Generic Database library installed via Composer.

```
wizard/
├── index.php          ← entry point (initialises template + i18n)
├── api.php            ← JSON REST API consumed by wizard.js
├── assets/
│   ├── wizard.css     ← styles
│   └── icons/         ← 15 database SVG icons
├── data/              ← persistent JSON/TOML state (git-ignored recommended)
│   ├── settings.toml        ← global settings (pagination + data file paths)
│   ├── active_profile.json  ← currently active connection
│   ├── profiles.json        ← all saved connections (Custom + Preset)
│   ├── queries.json         ← saved SQL queries
│   ├── query_history.json   ← executed query history (max 100)
│   ├── builders.json        ← saved QueryBuilder snippets
│   ├── builder_history.json ← executed QueryBuilder history (max 100)
│   ├── draft.json           ← draft SQL / QB snippets
│   └── profiles/            ← per-connection env + PHP files
│       ├── .custom_<name>            ← env variables (KEY="value")
│       └── custom_<name>.php         ← generated PHP connection file
├── includes/
│   ├── Autoload.php   ← file-level template loader
│   ├── Functions.php  ← utility functions (env, TOML, alerts…)
│   ├── Template.php   ← minimal PHP template engine
│   └── Translator.php ← i18n loader
├── js/
│   └── wizard.js      ← all frontend logic (vanilla JS)
├── locales/
│   ├── en.php         ← English strings
│   └── pt.php         ← Portuguese strings
└── templates/
    └── index.php      ← main HTML template (Bootstrap 5)
```

---

## Features

| Feature | Description |
|---|---|
| **Multi-engine** | Native (MySQLi, PgSQL, SQLSrv, OCI, Firebird, SQLite), PDO, ODBC, PDO+ODBC, Flat Files (CSV, INI, JSON, NEON, XML, YAML) |
| **Preset connections** | Instantly activate environment-based connections without form fill-in |
| **Custom connections** | Full form with host/port/database/user/password/charset/options |
| **Connection test** | Validates credentials before saving |
| **Manage connections** | Table view with activate / edit / delete actions |
| **Raw SQL executor** | Execute any SQL against the active connection |
| **Prepared statements** | `?`, `$1`, `:name` placeholders with dynamic parameter fields |
| **SQL → QueryBuilder** | Convert SQL to PHP QueryBuilder API calls (supports UNION, EXISTS, subqueries) |
| **Query persistence** | Save, load and delete named queries |
| **Execution history** | Last 100 executed queries and builders with timestamps and row counts |
| **Draft system** | Save and restore work-in-progress SQL / QB snippets |
| **Settings** | Configurable pagination limits and data file paths, persisted in `settings.toml` |
| **i18n** | Full EN / PT-BR translation with URL-based language switch |
| **Debug mode** | Toggle via URL `?debug=1` or UI checkbox to expose raw connection objects |

---

## Wizard Steps

The UI is organised as a three-step wizard:

```
[ ① Connection ] ──── [ ② Query ] ──── [ ③ Builder ]
```

Steps 2 and 3 are disabled until an active connection is configured.

---

## Connection Management

### Preset Connection

A preset uses environment variables already present in the project `.env` file. No credentials are stored in the wizard — the generated PHP file simply reads `$_ENV`.

```
Module → Engine → Driver → Instance Type → [Test] → [Confirm]
```

Supported modules: `Chainable`, `Fluent`, `StaticArgs`, `StaticArray`.

Instance types:
- **Specific** — uses the concrete driver class (`MySQLiConnection`, `PDOConnection`, …)
- **Strategy / Facade** — uses the abstract `Connection` facade

### Custom Connection

A custom connection stores credentials in `data/profiles/` as a pair of files:

| File | Content |
|---|---|
| `data/profiles/.custom_<name>` | Shell-style env file (`KEY="value"`) |
| `data/profiles/custom_<name>.php` | Auto-generated PHP bootstrap that reads the env file and creates the connection |

The wizard generates the PHP file automatically. You can require it directly in your own scripts:

```php
require_once __DIR__ . '/wizard/data/profiles/custom_my_connection.php';
// $context is the connected GenericDatabase instance
$qb = \GenericDatabase\Engine\MySQLi\QueryBuilder\Builder::with($context);
```

### Active Connection

Only one connection is active at a time. It is stored in `data/active_profile.json`:

```json
{
  "active_connection": {
    "source": "Custom",
    "name": "custom_fluent_native_mysqli",
    "module": "Chainable",
    "engine": "Native",
    "driver": "MySQLi",
    "type": "Specific",
    "env_file": "profiles/.custom_fluent_native_mysqli",
    "php_file": "profiles/custom_fluent_native_mysqli.php"
  }
}
```

Clicking the active connection badge in the breadcrumb opens a JSON viewer of this file.

---

## Query Executor (Step 2)

### Query Types

| Type | Description |
|---|---|
| **Raw SQL** | Executed via `$conn->query($sql)` |
| **Prepared Statement** | Executed via `$conn->prepare($sql, ...$params)` |

### Placeholder Formats (Prepared)

| Format | Example |
|---|---|
| Question mark | `WHERE id = ?` |
| Numbered | `WHERE id = $1` |
| Named | `WHERE id = :id` |

Dynamic parameter input fields are generated automatically based on the number and type of placeholders found in the SQL.

### Results

SELECT queries return a paginated result table. DML queries return the affected row count. Both show execution time in milliseconds.

### Saving and History

- **Save Query** — assigns a name and persists to `data/queries.json`
- **Save Draft** — optional alias, persisted to `data/draft.json`
- **History** — the last 100 executions are stored in `data/query_history.json`

---

## Query Builder (Step 3)

Paste any SQL into the input area and the wizard converts it on-the-fly to the PHP QueryBuilder API:

```sql
SELECT u.id, u.name FROM users u
WHERE EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)
ORDER BY u.name ASC LIMIT 10
```

↓ becomes ↓

```php
$qb = Builder::with($context)
    ->table('users', 'u')
    ->select('u.id', 'u.name')
    ->whereExists(fn($sub) => $sub->table('orders', 'o')->select(1)->where('o.user_id', '=', 'u.id'))
    ->orderBy('u.name', 'ASC')
    ->limit(10);
```

Supported SQL constructs:

- `SELECT` with aliases and multi-table
- `WHERE` / `AND` / `OR` with operators (`=`, `!=`, `>`, `<`, `LIKE`, `IN`, `BETWEEN`, `IS NULL`, …)
- `EXISTS` / `NOT EXISTS` with correlated subqueries
- `JOIN` (`INNER`, `LEFT`, `RIGHT`, `CROSS`)
- `GROUP BY` / `HAVING`
- `ORDER BY` (multiple columns, ASC / DESC)
- `LIMIT` / `OFFSET`
- `UNION` / `UNION ALL`
- Nested subqueries

Click **Execute** to run the generated code directly against the active connection.

---

## Settings

All global settings are stored in `data/settings.toml` and editable through the **Options → Settings** modal. Saving any value triggers an automatic page reload so the new configuration takes effect immediately.

### Pagination

| Setting | Default | Description |
|---|---|---|
| `queries_page_size` | 10 | Items per page in Saved Queries list |
| `history_page_size` | 10 | Items per page in Query Execution History |
| `connections_page_size` | 10 | Items per page in Manage Connections table |
| `builders_page_size` | 10 | Items per page in Saved Builders list |
| `builder_history_page_size` | 10 | Items per page in Builder History |

Valid range: **1 – 100** per setting.

### Data File Paths

| Setting | Default | Description |
|---|---|---|
| `profiles_dir` | `/profiles` | Directory for per-connection files (relative to `data/`) |
| `active_profile_file` | `/active_profile.json` | Active connection metadata |
| `profiles_file` | `/profiles.json` | All registered connections |
| `queries_file` | `/queries.json` | Saved named SQL queries |
| `query_history_file` | `/query_history.json` | Query execution history |
| `builders_file` | `/builders.json` | Saved QueryBuilder snippets |
| `builder_history_file` | `/builder_history.json` | Builder execution history |
| `draft_file` | `/draft.json` | Draft SQL / QB snippets |

Path values must start with `/` and must not contain `..`.

### settings.toml format

```toml
# Wizard Configuration File
queries_page_size = 10
history_page_size = 10
connections_page_size = 10
builders_page_size = 10
builder_history_page_size = 10

profiles_dir = "/profiles"
active_profile_file = "/active_profile.json"
profiles_file = "/profiles.json"
queries_file = "/queries.json"
query_history_file = "/query_history.json"
builders_file = "/builders.json"
builder_history_file = "/builder_history.json"
draft_file = "/draft.json"
```

Reading and writing TOML is handled by `read_toml_file()` and `write_toml_file()` in `includes/Functions.php` — no external library required.

---

## Data Files

All state is stored as plain files inside `data/`. It is recommended to add `wizard/data/` to `.gitignore` to avoid committing credentials.

| File | Format | Purpose |
|---|---|---|
| `settings.toml` | TOML | Global pagination settings (primary) |
| `active_profile.json` | JSON | Currently active connection metadata |
| `profiles.json` | JSON | All registered connections (Custom + Preset) |
| `queries.json` | JSON | Saved named SQL queries |
| `query_history.json` | JSON | Last 100 executed queries |
| `builders.json` | JSON | Saved named QueryBuilder snippets |
| `builder_history.json` | JSON | Last 100 executed builder snippets |
| `draft.json` | JSON | Draft (unsaved) SQL and QB snippets |
| `profiles/.custom_<name>` | ENV | Per-connection credentials |
| `profiles/custom_<name>.php` | PHP | Auto-generated connection bootstrap |
| `profiles/preset_<name>.php` | PHP | Preset connection bootstrap |

---

## Path Constants

Only two constants are defined statically in `index.php` and `api.php`:

```php
define('WIZARD_DATA_DIR',    __DIR__ . '/data');
define('WIZARD_SETTINGS_FILE', WIZARD_DATA_DIR . '/settings.toml');
```

All other `WIZARD_*` constants — `WIZARD_PROFILES_DIR`, `WIZARD_QUERIES_FILE`, etc. — are derived automatically at runtime from `settings.toml`. Path values (those starting with `/`) are expanded to absolute paths by prepending `WIZARD_DATA_DIR`:

```php
// Derived automatically from settings.toml at startup:
// profiles_dir = "/profiles"  →  WIZARD_PROFILES_DIR = WIZARD_DATA_DIR . '/profiles'
// queries_file = "/queries.json"  →  WIZARD_QUERIES_FILE = WIZARD_DATA_DIR . '/queries.json'
// ...
```

To relocate any file or directory, edit the value in `settings.toml` via the **Options → Settings** modal (or directly in the file) — no PHP code changes required.

---

## Internationalisation

Language is selected via the `lang` query parameter (`?lang=en` or `?lang=pt`). A language toggle is available in the Options dropdown.

Translation strings live in `locales/en.php` and `locales/pt.php` as plain PHP arrays. The `Translator` class (`includes/Translator.php`) loads the appropriate file and resolves keys with optional `{{variable}}` interpolation:

```php
$t = new Translator('en');
echo $t->t('connection_created');          // "Connection created successfully!"
echo $t->t('query_rows', ['n' => 42]);    // "42 rows"
```

To add a new language, create `locales/<code>.php` returning an array with the same keys as `en.php`, then add the code to the allowlist in `index.php`:

```php
$lang = in_array($lang, ['en', 'pt', 'es']) ? $lang : 'en';
```

---

## API Reference

All API calls go to `api.php` and use the `action` parameter (GET or POST).

### `test_connection` · POST

Test a database connection without saving it.

| Parameter | Type | Description |
|---|---|---|
| `module` | string | `Chainable`, `Fluent`, `StaticArgs`, `StaticArray` |
| `engine` | string | `native`, `pdo`, `odbc`, `pdo_odbc`, `flat_files` |
| `driver` | string | `mysql`, `pgsql`, `sqlsrv`, `oci`, `firebird`, `sqlite`, … |
| `instance_type` | string | `specific` or `strategy` |
| `host`, `port`, `database`, `username`, `password`, `charset` | string | Connection credentials |
| `options` | string | JSON or `key => value` options |

Response: `{ success, message, debug? }`

---

### `complete_connection` · POST

Save a new custom connection and set it as active. Same parameters as `test_connection`, plus `connection_name`.

Response: `{ success, message, files: { env, php }, config }`

---

### `update_connection` · POST

Update an existing custom connection.

| Parameter | Type | Description |
|---|---|---|
| `edit_connection_name` | string | Current connection name to update |
| `connection_name` | string | New name (may be the same) |
| + all `complete_connection` params | | |

Response: `{ success, message, config }`

---

### `confirm_preset` · POST

Activate a preset connection.

| Parameter | Type | Description |
|---|---|---|
| `module` | string | Module identifier |
| `engine` | string | Engine identifier |
| `driver` | string | Driver identifier |
| `instance_type` | string | `specific` or `strategy` |

Response: `{ success, message, config }`

---

### `get_connections` · GET

Return all saved connections and the current active connection name.

Response: `{ connections: [], active_name: string }`

---

### `get_connection_fields` · GET

Return HTML form fields for a given `driver`/`engine` combination, pre-filled with existing connection data when `connection_name` is supplied.

---

### `activate_connection` · POST

Set a saved connection as active.

| Parameter | Type |
|---|---|
| `connection_name` | string |

---

### `delete_connection` · POST

Delete a custom connection and all associated files.

| Parameter | Type |
|---|---|
| `connection_name` | string |

---

### `execute_query` · POST

Execute a SQL query against the active connection.

| Parameter | Type | Description |
|---|---|---|
| `sql` | string | SQL to execute |
| `query_type` | string | `raw` or `prepared` |
| `placeholder_type` | string | `question`, `numbered`, `named` |
| `params` | array | Bound parameter values |

Response: `{ success, rows?, count?, columns?, affected_rows?, duration_ms }`

---

### `save_query` · POST

Persist a named SQL query.

| Parameter | Type |
|---|---|
| `name` | string |
| `sql` | string |
| `query_type` | string |

---

### `delete_query` · POST / `get_queries` · GET

Manage saved queries. `delete_query` requires `query_id`.

---

### `execute_builder` · POST

Execute QueryBuilder PHP code against the active connection.

| Parameter | Type |
|---|---|
| `code` | string |
| `sql` | string |

---

### `sql_to_qb` · POST

Convert SQL to QueryBuilder PHP code without executing.

| Parameter | Type |
|---|---|
| `sql` | string |

Response: `{ success, code }`

---

### `get_settings` · GET

Return current settings.

Response: `{ success, settings: { queries_page_size, … } }`

---

### `update_settings` · POST

Persist one or more setting values (each as a POST field). Values outside 1–100 are ignored.

Response: `{ success, settings }`

---

### `clear_active_connection` · POST

Deselect the active connection without deleting any files.

---

### `get_config` · GET

Return the full active connection configuration (used by the active connection badge modal).

---

## Extending the Wizard

### Add a new database driver

1. Add the driver key to `$ENGINE_DRIVERS` in `api.php`
2. Add the method name to `$DRIVER_METHODS`
3. Add the env key list to `$DRIVER_ENV_KEYS`
4. Add the env prefix to `$DRIVER_ENV_PREFIX`
5. Add the label to `$DRIVER_LABELS`
6. Mirror the same additions in the `CONFIG` object in `wizard.js`
7. Add a 32 × 32 SVG icon to `assets/icons/` and register it in `CONFIG.driverIcons`

### Add a new language

1. Copy `locales/en.php` to `locales/<code>.php` and translate values
2. Add `'<code>'` to the `in_array` check in `index.php`
3. Add the language switch label (e.g. `'lang_switch' => 'Español'`) to the new locale file

### Change a data file location

Edit the relevant path key in `data/settings.toml` (or use **Options → Settings** in the UI). The new path takes effect on the next page load — no PHP code changes required.
