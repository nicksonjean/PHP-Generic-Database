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

### Chainable Methods

```php
// Create a new database connection using PDO engine with mysql driver in the chainable methods format
$connection = new PDOConnection();
$connection
->setDriver('mysql')
->setHost($_ENV['MYSQL_HOST'])
->setPort(+$_ENV['MYSQL_PORT'])
->setDatabase($_ENV['MYSQL_DATABASE'])
->setUser($_ENV['MYSQL_USERNAME'])
->setPassword($_ENV['MYSQL_PASSWORD'])
->setCharset('utf8')
->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
->setException(true)
->connect();
```

```php
// Create a new database connection using PDO engine with pgsql driver in the chainable methods format
$connection = new PDOConnection();
$connection
->setDriver('pgsql')
->setHost($_ENV['PGSQL_HOST'])
->setPort(+$_ENV['PGSQL_PORT'])
->setDatabase($_ENV['PGSQL_DATABASE'])
->setUser($_ENV['PGSQL_USER'])
->setPassword($_ENV['PGSQL_PASSWORD'])
->setCharset('utf8')
->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
->setException(true)
->connect();
```

```php
// Create a new database connection using PDO engine with sqlsrv driver in the chainable methods format
$connection = new PDOConnection();
$connection
->setDriver('sqlsrv')
->setHost($_ENV['SQLSRV_HOST'])
->setPort(+$_ENV['SQLSRV_PORT'])
->setDatabase($_ENV['SQLSRV_DATABASE'])
->setUser($_ENV['SQLSRV_USER'])
->setPassword($_ENV['SQLSRV_PASSWORD'])
->setCharset('utf8')
->setOptions([
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
->setException(true)
->connect();
```

```php
// Create a new database connection using PDO engine with oci driver in the chainable methods format
$connection = new PDOConnection();
$connection
->setDriver('oci')
->setHost($_ENV['OCI_HOST'])
->setPort(+$_ENV['OCI_PORT'])
->setDatabase($_ENV['OCI_DATABASE'])
->setUser($_ENV['OCI_USER'])
->setPassword($_ENV['OCI_PASSWORD'])
->setCharset('utf8')
->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
->setException(true)
->connect();
```

```php
// Create a new database connection using PDO engine with firebird driver in the chainable methods format
$connection = new PDOConnection();
$connection
->setDriver('firebird')
->setHost($_ENV['FBIRD_HOST'])
->setPort(+$_ENV['FBIRD_PORT'])
->setDatabase($_ENV['FBIRD_DATABASE'])
->setUser($_ENV['FBIRD_USER'])
->setPassword($_ENV['FBIRD_PASSWORD'])
->setCharset('utf8')
->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
->setException(true)
->connect();
```

```php
// Create a new database connection using PDO engine with sqlite driver in the chainable methods format
$connection = new PDOConnection();
$connection
->setDriver('sqlite')
->setDatabase($_ENV['SQLITE_DATABASE'])
->setCharset('utf8')
->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
->setException(true)
->connect();
```

```php
// Create a new database connection using PDO engine with sqlite in memory driver in the chainable methods format
$connection = new PDOConnection();
$connection
->setDriver('sqlite')
->setDatabase($_ENV['SQLITE_DATABASE_MEMORY'])
->setCharset('utf8')
->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
])
->setException(true)
->connect();
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
