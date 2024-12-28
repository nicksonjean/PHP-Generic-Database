# Connection

## Connection Modules

The `GenericDatabase\Modules\Chainable` class is responsible for establishing and managing database connections. It uses a strategy pattern to support different database engines. The class provides methods for connecting to a database, executing queries, fetching results, and managing the connection state.

## Example Usage

### Chainable Methods

```php
// Implicit and simplified module loading with all environment variables
use GenericDatabase\Modules\Chainable;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();
```

```php
// Create a new database connection using MySQLi engine for MySQL/MariaDB dialects in the chainable methods format
$connection = Chainable::nativeMySQLi(
    env: $_ENV,
    persistent: true,
    strategy: true
)
->connect();
```

```php
// Create a new database connection using PgSQL engine in the chainable methods format
$connection = Chainable::nativePgSQL(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using SQLSrv engine for SQLSrv/MSSQL/DBLib dialects in the chainable methods format
$connection = Chainable::nativeSQLSrv(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using OCI8 engine in the chainable methods format
$connection = Chainable::nativeOCI(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using Firebird/Interbase engine for Firebird/Interbase dialects in the chainable methods format
$connection = Chainable::nativeFirebird(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using SQLite3 engine in the chainable methods format
$connection = Chainable::nativeSQLite(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using SQLite3 in Memory engine in the chainable methods format
$connection = Chainable::nativeMemory(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using PDO MySQL engine for MySQL/MariaDB dialects in the chainable methods format
$connection = Chainable::pdoMySQL(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using PDO PgSQL engine in the chainable methods format
$connection = Chainable::pdoPgSQL(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using PDO SQLSrv engine for SQLSrv/MSSQL/DBLib dialects in the chainable methods format
$connection = Chainable::pdoSQLSrv(
    env: $_ENV,
    strategy: true
)->connect();
```

```php
// Create a new database connection using PDO OCI engine in the chainable methods format
$connection = Chainable::pdoOCI(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using PDO Firebird/Interbase engine for Firebird/Interbase dialects in the chainable methods format
$connection = Chainable::pdoFirebird(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using PDO SQLite engine in the chainable methods format
$connection = Chainable::pdoSQLite(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

```php
// Create a new database connection using PDO SQLite in Memory engine in the chainable methods format
$connection = Chainable::pdoMemory(
    env: $_ENV,
    persistent: true,
    strategy: true
)->connect();
```

## Code Analysis

### Main functionalities

- Establishing and managing database connections
- Supporting different database engines through the strategy pattern
- Executing queries and fetching results
- Managing the connection state (connecting, disconnecting, checking if connected)

### Public Methods

- `setStrategy(IConnection $strategy)`: Sets the strategy instance for the database connection.
- `getStrategy(): IConnection`: Returns the strategy instance for the database connection.
- `connect(): Connection`: Establishes a database connection using the strategy instance.
- `ping(): bool`: Pings the database server to check if the connection is still active.
- `disconnect(): void`: Disconnects from the database.
- `isConnected(): bool`: Checks if the connection is established.
- `quote(mixed ...$params): mixed`: Quotes a string for use in an SQL statement.
- `prepare(mixed ...$params): mixed`: Binds parameters to a prepared query.
- `query(mixed ...$params): mixed`: Executes an SQL statement and returns the result set.
- `exec(mixed ...$params): mixed`: Executes an SQL statement and returns the number of affected rows.
- `fetch(mixed ...$params): mixed`: Fetches the next row from the result set.
- `fetchAll(mixed ...$params): mixed`: Fetches all rows from the result set.
