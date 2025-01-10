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

### Fluent Design

```php
// Create a new database connection using MySQLi engine for MySQL/MariaDB dialects in the chainable methods format
$connection = Connection
::setEngine($_ENV['mysqli'])
::setHost($_ENV['MYSQL_HOST'])
::setPort((int)$_ENV['MYSQL_PORT'])
::setDatabase($_ENV['MYSQL_DATABASE'])
::setUser($_ENV['MYSQL_USERNAME'])
::setPassword($_ENV['MYSQL_PASSWORD'])
::setCharset($_ENV['MYSQL_CHARSET'])
::setOptions([
    MySQL::ATTR_PERSISTENT => false,
    MySQL::ATTR_AUTOCOMMIT => true,
    MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
    MySQL::ATTR_SET_CHARSET_NAME => "utf8",
    MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
    MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
    MySQL::ATTR_OPT_READ_TIMEOUT => 30,
    MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
])
::setException(true)
->connect();
```

```php
// Create a new database connection using PgSQL engine in the chainable methods format
$connection = Connection
::setEngine('pgsql')
::setHost($_ENV['PGSQL_HOST'])
::setPort((int)$_ENV['PGSQL_PORT'])
::setDatabase($_ENV['PGSQL_DATABASE'])
::setUser($_ENV['PGSQL_USER'])
::setPassword($_ENV['PGSQL_PASSWORD'])
::setCharset('utf8')
::setOptions([
    PgSQL::ATTR_PERSISTENT => true,
    PgSQL::ATTR_CONNECT_ASYNC => true,
    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
])
::setException(true)
->connect();
```

```php
// Create a new database connection using SQLSrv engine for SQLSrv/MSSQL/DBLib dialects in the chainable methods format
$connection = Connection
::setEngine('sqlsrv')
::setHost($_ENV['SQLSRV_HOST'])
::setPort((int)$_ENV['SQLSRV_PORT'])
::setDatabase($_ENV['SQLSRV_DATABASE'])
::setUser($_ENV['SQLSRV_USER'])
::setPassword($_ENV['SQLSRV_PASSWORD'])
::setCharset('utf8')
::setOptions([
    SQLSrv::ATTR_PERSISTENT => true,
    SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
])
::setException(true)
->connect();
```

```php
// Create a new database connection using ORACLE8 engine in the chainable methods format
$connection = Connection
::setEngine('oci')
::setHost($_ENV['OCI_HOST'])
::setPort((int)$_ENV['OCI_PORT'])
::setDatabase($_ENV['OCI_DATABASE'])
::setUser($_ENV['OCI_USER'])
::setPassword($_ENV['OCI_PASSWORD'])
::setCharset('utf8')
::setOptions([
    OCI::ATTR_PERSISTENT => true,
    OCI::ATTR_CONNECT_TIMEOUT => 28800,
])
::setException(true)
->connect();
```

```php
// Create a new database connection using Firebird/Interbase engine for Firebird/Interbase dialects in the chainable methods format
$connection = Connection
::setEngine('firebird')
::setHost($_ENV['FBIRD_HOST'])
::setPort((int)$_ENV['FBIRD_PORT'])
::setDatabase($_ENV['FBIRD_DATABASE'])
::setUser($_ENV['FBIRD_USER'])
::setPassword($_ENV['FBIRD_PASSWORD'])
::setCharset('utf8')
::setOptions([
    Firebird::ATTR_PERSISTENT => true,
    Firebird::ATTR_CONNECT_TIMEOUT => 28800,
])
::setException(true)
->connect();
```

```php
// Create a new database connection using SQLite3 engine in the chainable methods format
$connection = Connection
::setEngine('sqlite')
::setDatabase($_ENV['SQLITE_DATABASE'])
::setCharset('utf8')
::setOptions([
    SQLite::ATTR_OPEN_READONLY => false,
    SQLite::ATTR_OPEN_READWRITE => true,
    SQLite::ATTR_OPEN_CREATE => true,
    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
    SQLite::ATTR_PERSISTENT => true,
    SQLite::ATTR_AUTOCOMMIT => true
])
::setException(true)
->connect();
```

```php
// Create a new database connection using SQLite3 in Memory engine in the chainable methods format
$connection = Connection;
::setEngine('sqlite')
::setDatabase($_ENV['SQLITE_DATABASE_MEMORY'])
::setCharset('utf8')
::setOptions([
    SQLite::ATTR_OPEN_READONLY => false,
    SQLite::ATTR_OPEN_READWRITE => true,
    SQLite::ATTR_OPEN_CREATE => true,
    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
    SQLite::ATTR_PERSISTENT => true,
    SQLite::ATTR_AUTOCOMMIT => true
])
::setException(true)
->connect();
```

```php
// Create a new database connection using PDO MySQL engine for MySQL/MariaDB dialects in the chainable methods format
$connection = Connection
::setEngine('pdo')
::setDriver('mysql')
::setHost($_ENV['MYSQL_HOST'])
::setPort((int)$_ENV['MYSQL_PORT'])
::setDatabase($_ENV['MYSQL_DATABASE'])
::setUser($_ENV['MYSQL_USERNAME'])
::setPassword($_ENV['MYSQL_PASSWORD'])
::setCharset('utf8')
::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
::setException(true)
->connect();
```

```php
// Create a new database connection using PDO PgSQL engine in the chainable methods format
$connection = Connection
::setEngine('pdo')
::setDriver('pgsql')
::setHost($_ENV['PGSQL_HOST'])
::setPort((int)$_ENV['PGSQL_PORT'])
::setDatabase($_ENV['PGSQL_DATABASE'])
::setUser($_ENV['PGSQL_USER'])
::setPassword($_ENV['PGSQL_PASSWORD'])
::setCharset('utf8')
::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
::setException(true)
->connect();
```

```php
// Create a new database connection using PDO SQLSrv engine for SQLSrv/MSSQL/DBLib dialects in the chainable methods format
$connection = Connection
::setEngine('pdo')
::setDriver('sqlsrv')
::setHost($_ENV['SQLSRV_HOST'])
::setPort((int)$_ENV['SQLSRV_PORT'])
::setDatabase($_ENV['SQLSRV_DATABASE'])
::setUser($_ENV['SQLSRV_USER'])
::setPassword($_ENV['SQLSRV_PASSWORD'])
::setCharset('utf8')
::setOptions([
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
::setException(true)
->connect();
```

```php
// Create a new database connection using PDO ORACLE engine in the chainable methods format
$connection = Connection
::setEngine('pdo')
::setDriver('oci')
::setHost($_ENV['OCI_HOST'])
::setPort((int)$_ENV['OCI_PORT'])
::setDatabase($_ENV['OCI_DATABASE'])
::setUser($_ENV['OCI_USER'])
::setPassword($_ENV['OCI_PASSWORD'])
::setCharset('utf8')
::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
::setException(true)
->connect();
```

```php
// Create a new database connection using PDO Firebird/Interbase engine for Firebird/Interbase dialects in the chainable methods format
$connection = Connection
::setEngine('pdo')
::setDriver('firebird')
::setHost($_ENV['FBIRD_HOST'])
::setPort((int)$_ENV['FBIRD_PORT'])
::setDatabase($_ENV['FBIRD_DATABASE'])
::setUser($_ENV['FBIRD_USER'])
::setPassword($_ENV['FBIRD_PASSWORD'])
::setCharset('utf8')
::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
::setException(true)
->connect();
```

```php
// Create a new database connection using PDO SQLite engine in the chainable methods format
$connection = Connection
::setEngine('pdo')
::setDriver('sqlite')
::setDatabase($_ENV['SQLITE_DATABASE'])
::setCharset('utf8')
::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
::setException(true)
->connect();
```

```php
// Create a new database connection using PDO SQLite in Memory engine in the chainable methods format
$connection = Connection
::setEngine('pdo')
::setDriver('sqlite')
::setDatabase($_ENV['SQLITE_DATABASE_MEMORY'])
::setCharset('utf8')
::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
::setException(true)
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
