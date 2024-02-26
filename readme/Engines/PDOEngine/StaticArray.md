# PDOEngine

## PDOEngine Connection

The `GenericDatabase\PDOEngine` class is responsible for establishing and managing database connections. It uses a strategy pattern to support different database engines. The class provides methods for connecting to a database, executing queries, fetching results, and managing the connection state.

## Example Usage

### Load Class and Types Explicitly

```php
// Explicit and simplified module loading with all environment variables
use GenericDatabase\PDOEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();
```

### Static Array

```php
// Create a new database connection using PDO engine with mysql driver in the static array format
$connection = PDOEngine::new([
    'driver' => 'mysql',
    'host' => $_ENV['MYSQL_HOST'],
    'port' => +$_ENV['MYSQL_PORT'],
    'database' => $_ENV['MYSQL_DATABASE'],
    'user' => $_ENV['MYSQL_USER'],
    'password' => $_ENV['MYSQL_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])
->connect();
```

```php
// Create a new database connection using PDO engine with mysql driver in the static array format
$connection = PDOEngine::new([
    'driver' => 'pgsql',
    'host' => $_ENV['PGSQL_HOST'],
    'port' => +$_ENV['PGSQL_PORT'],
    'database' => $_ENV['PGSQL_DATABASE'],
    'user' => $_ENV['PGSQL_USER'],
    'password' => $_ENV['PGSQL_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();
```

```php
// Create a new database connection using PDO engine with mysql driver in the static array format
$sqlsrv = PDOEngine::new([
    'driver' => 'sqlsrv',
    'host' => $_ENV['SQLSRV_HOST'],
    'port' => +$_ENV['SQLSRV_PORT'],
    'database' => $_ENV['SQLSRV_DATABASE'],
    'user' => $_ENV['SQLSRV_USER'],
    'password' => $_ENV['SQLSRV_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();
```

```php
// Create a new database connection using PDO engine with mysql driver in the static array format
$oci = PDOEngine::new([
    'driver' => 'oci',
    'host' => $_ENV['OCI_HOST'],
    'port' => +$_ENV['OCI_PORT'],
    'database' => $_ENV['OCI_DATABASE'],
    'user' => $_ENV['OCI_USER'],
    'password' => $_ENV['OCI_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();
```

```php
// Create a new database connection using PDO engine with mysql driver in the static array format
$firebird = PDOEngine::new([
    'driver' => 'firebird',
    'host' => $_ENV['FIREBIRD_HOST'],
    'port' => +$_ENV['FIREBIRD_PORT'],
    'database' => $_ENV['FIREBIRD_DATABASE'],
    'user' => $_ENV['FIREBIRD_USER'],
    'password' => $_ENV['FIREBIRD_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();
```

```php
// Create a new database connection using PDO engine with mysql driver in the static array format
$sqlite2 = PDOEngine::new([
    'driver' => 'sqlite',
    'database' => $_ENV['SQLITE_DATABASE'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();
```

```php
// Create a new database connection using PDO engine with sqlite in memory driver in the static array format
$memory = PDOEngine::new([
    'driver' => 'sqlite',
    'database' => 'memory',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();
```

## Code Analysis

### Main functionalities

- Establishing and managing database connections
- Supporting different database engines through the strategy pattern
- Executing queries and fetching results
- Managing the connection state (connecting, disconnecting, checking if connected)

### Public Methods

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
