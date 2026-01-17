# Connection

## Connection Strategy

The `GenericDatabase\Connection` class is responsible for establishing and managing database connections. It uses a strategy pattern to support different database engines. The class provides methods for connecting to a database, executing queries, fetching results, and managing the connection state.

## Example Usage

### Load Class and Types Explicitly

```php
// Explicit and simplified module loading with all environment variables
use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQli\MySQL;
use GenericDatabase\Engine\PgSQL\PgSQL;
use GenericDatabase\Engine\SQLSrv\SQLSrv;
use GenericDatabase\Engine\OCI\OCI;
use GenericDatabase\Engine\Firebird\Firebird;
use GenericDatabase\Engine\SQLite\SQLite;
use PDO;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();
```

### Static Array

```php
// Create a new database connection using MySQLi engine in the static arguments format
$connection = Connection::new(
    engine: 'mysqli',
    host: $_ENV['MYSQL_HOST'],
    port: (int)$_ENV['MYSQL_PORT'],
    database: $_ENV['MYSQL_DATABASE'],
    user: $_ENV['MYSQL_USERNAME'],
    password: $_ENV['MYSQL_PASSWORD'],
    charset: 'utf8',
    options: [
        MySQL::ATTR_PERSISTENT => false,
        MySQL::ATTR_AUTOCOMMIT => true,
        MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        MySQL::ATTR_SET_CHARSET_NAME => "utf8",
        MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
        MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
        MySQL::ATTR_OPT_READ_TIMEOUT => 30,
        MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using PgSQL engine in the static arguments format
$connection = Connection::new(
    engine: 'pgsql',
    host: $_ENV['PGSQL_HOST'],
    port: (int)$_ENV['PGSQL_PORT'],
    database: $_ENV['PGSQL_DATABASE'],
    user: $_ENV['PGSQL_USER'],
    password: $_ENV['PGSQL_PASSWORD'],
    charset: 'utf8',
    options: [
        PgSQL::ATTR_PERSISTENT => true,
        PgSQL::ATTR_CONNECT_ASYNC => true,
        PgSQL::ATTR_CONNECT_FORCE_NEW => true,
        PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using SQLSrv engine for SQLSrv/MSSQL/DBLib dialects in the static arguments format
$connection = Connection::new(
    engine: 'sqlsrv',
    host: $_ENV['SQLSRV_HOST'],
    port: (int)$_ENV['SQLSRV_PORT'],
    database: $_ENV['SQLSRV_DATABASE'],
    user: $_ENV['SQLSRV_USER'],
    password: $_ENV['SQLSRV_PASSWORD'],
    charset: 'utf8',
    options: [
        SQLSrv::ATTR_PERSISTENT => true,
        SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using OCI8 engine in the static arguments format
$connection = Connection::new(
    engine: 'oci',
    host: $_ENV['OCI_HOST'],
    port: (int)$_ENV['OCI_PORT'],
    database: $_ENV['OCI_DATABASE'],
    user: $_ENV['OCI_USER'],
    password: $_ENV['OCI_PASSWORD'],
    charset: 'utf8',
    options: [
        OCI::ATTR_PERSISTENT => true,
        OCI::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using Firebird/Interbase engine for Firebird/Interbase dialects in the static arguments format
$connection = Connection::new(
    engine: 'firebird',
    host: $_ENV['FBIRD_HOST'],
    port: (int)$_ENV['FBIRD_PORT'],
    database: $_ENV['FBIRD_DATABASE'],
    user: $_ENV['FBIRD_USER'],
    password: $_ENV['FBIRD_PASSWORD'],
    charset: 'utf8',
    options: [
        Firebird::ATTR_PERSISTENT => true,
        Firebird::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using SQLite3 engine in the static arguments format
$connection = Connection::new(
    engine: 'sqlite',
    database: $_ENV['SQLITE_DATABASE'],
    charset: 'utf8',
    options: [
        SQLite::ATTR_OPEN_READONLY => false,
        SQLite::ATTR_OPEN_READWRITE => true,
        SQLite::ATTR_OPEN_CREATE => true,
        SQLite::ATTR_CONNECT_TIMEOUT => 28800,
        SQLite::ATTR_PERSISTENT => true,
        SQLite::ATTR_AUTOCOMMIT => true
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using SQLite3 in Memory engine in the static arguments format
$connection = Connection::new(
    engine: 'sqlite',
    database: $_ENV['SQLITE_DATABASE_MEMORY'],
    charset: 'utf8',
    options: [
        SQLite::ATTR_OPEN_READONLY => false,
        SQLite::ATTR_OPEN_READWRITE => true,
        SQLite::ATTR_OPEN_CREATE => true,
        SQLite::ATTR_CONNECT_TIMEOUT => 28800,
        SQLite::ATTR_PERSISTENT => true,
        SQLite::ATTR_AUTOCOMMIT => true
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using PDO MySQL engine for MySQL/MariaDB dialects in the static arguments format
$connection = Connection::new(
    engine: 'pdo',
    driver: 'mysql',
    host: $_ENV['MYSQL_HOST'],
    port: (int)$_ENV['MYSQL_PORT'],
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
// Create a new database connection using PDO PgSQL engine in the static arguments format
$connection = Connection::new(
    engine: 'pdo',
    driver: 'pgsql',
    host: $_ENV['PGSQL_HOST'],
    port: (int)$_ENV['PGSQL_PORT'],
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
)
->connect();
```

```php
// Create a new database connection using PDO SQLSrv engine for SQLSrv/MSSQL/DBLib dialects in the static arguments format
$connection = Connection::new(
    engine: 'pdo',
    driver: 'sqlsrv',
    host: $_ENV['SQLSRV_HOST'],
    port: (int)$_ENV['SQLSRV_PORT'],
    database: $_ENV['SQLSRV_DATABASE'],
    user: $_ENV['SQLSRV_USER'],
    password: $_ENV['SQLSRV_PASSWORD'],
    charset: 'utf8',
    options: [
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    exception: true
)
->connect();
```

```php
// Create a new database connection using PDO OCI engine in the static arguments format
$connection = Connection::new(
    engine: 'pdo',
    driver: 'oci',
    host: $_ENV['OCI_HOST'],
    port: (int)$_ENV['OCI_PORT'],
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
)
->connect();
```

```php
// Create a new database connection using PDO Firebird/Interbase engine for Firebird/Interbase dialects in the static arguments format
$connection = Connection::new(
    engine: 'pdo',
    driver: 'firebird',
    host: $_ENV['FBIRD_HOST'],
    port: (int)$_ENV['FBIRD_PORT'],
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
)
->connect();
```

```php
// Create a new database connection using PDO SQLite engine in the static arguments format
$connection = Connection::new(
    engine: 'pdo',
    driver: 'sqlite',
    database: $_ENV['SQLITE_DATABASE'],
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
// Create a new database connection using PDO SQLite in Memory engine in the static arguments format
$connection = Connection::new(
    engine: 'pdo',
    driver: 'sqlite',
    database: $_ENV['SQLITE_DATABASE_MEMORY'],
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
