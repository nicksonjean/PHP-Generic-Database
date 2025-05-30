# PDOConnection

## PDOConnection Connection

The `GenericDatabase\PDOConnection` class is responsible for establishing and managing database connections. It uses a strategy pattern to support different database engines. The class provides methods for connecting to a database, executing queries, fetching results, and managing the connection state.

## Example Usage

### Load Class and Types Explicitly

```php
// Explicit and simplified module loading with all environment variables
use GenericDatabase\PDOConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();
```

### Static Arguments

```php
// Create a new database connection using PDO engine with mysql driver in the static arguments format
$connection = PDOConnection::new(
    driver: 'mysql',
    host: $_ENV['MYSQL_HOST'],
    port: +$_ENV['MYSQL_PORT'],
    database: $_ENV['MYSQL_DATABASE'],
    user: $_ENV['MYSQL_USERNAME'],
    password: $_ENV['MYSQL_PASSWORD'],
    charset: 'utf8',
    options: [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using PDO engine with pgsql driver in the static arguments format
$connection = PDOConnection::new(
    driver: 'pgsql',
    host: $_ENV['PGSQL_HOST'],
    port: +$_ENV['PGSQL_PORT'],
    database: $_ENV['PGSQL_DATABASE'],
    user: $_ENV['PGSQL_USER'],
    password: $_ENV['PGSQL_PASSWORD'],
    charset: 'utf8',
    options: [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    exception: true
)->connect();
```

```php
// Create a new database connection using PDO engine with sqlsrv driver in the static arguments format
$connection = PDOConnection::new(
    driver: 'sqlsrv',
    host: $_ENV['SQLSRV_HOST'],
    port: +$_ENV['SQLSRV_PORT'],
    database: $_ENV['SQLSRV_DATABASE'],
    user: $_ENV['SQLSRV_USER'],
    password: $_ENV['SQLSRV_PASSWORD'],
    charset: 'utf8',
    options: [
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    exception: true
)->connect();
```

```php
// Create a new database connection using PDO engine with oci driver in the static arguments format
$connection = PDOConnection::new(
    driver: 'oci',
    host: $_ENV['OCI_HOST'],
    port: +$_ENV['OCI_PORT'],
    database: $_ENV['OCI_DATABASE'],
    user: $_ENV['OCI_USER'],
    password: $_ENV['OCI_PASSWORD'],
    charset: 'utf8',
    options: [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    exception: true
)->connect();
```

```php
// Create a new database connection using PDO engine with firebird driver in the static arguments format
$firebird = PDOConnection::new(
    driver: 'firebird',
    host: $_ENV['FBIRD_HOST'],
    port: +$_ENV['FBIRD_PORT'],
    database: $_ENV['FBIRD_DATABASE'],
    user: $_ENV['FBIRD_USER'],
    password: $_ENV['FBIRD_PASSWORD'],
    charset: 'utf8',
    options: [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    exception: true
)->connect();
```

```php
// Create a new database connection using PDO engine with sqlite driver in the static arguments format
$connection = PDOConnection::new(
    driver: 'sqlite',
    database: $_ENV['SQLITE_DATABASE'],
    charset: 'utf8',
    options: [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    exception: true
)->connect();
```

```php
// Create a new database connection using PDO engine with sqlite in memory driver in the static arguments format
$connection = PDOConnection::new(
    driver: 'sqlite',
    database: 'memory',
    charset: 'utf8',
    options: [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    exception: true
)->connect();
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
