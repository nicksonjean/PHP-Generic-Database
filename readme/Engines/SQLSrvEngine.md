# SQLSrvConnection

## SQLSrvConnection Connection

The `GenericDatabase\SQLSrvConnection` class is responsible for establishing and managing database connections. It uses a strategy pattern to support different database engines. The class provides methods for connecting to a database, executing queries, fetching results, and managing the connection state.

## Example Usage

### Load Class and Types Explicitly

```php
// Explicit and simplified module loading with all environment variables
use GenericDatabase\SQLSrvConnection;
use GenericDatabase\Engine\SQLSrv\SQLSrv;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();
```

### Chainable Methods

```php
// Create a new database connection using MySQLi engine in the chainable methods format
$connection = new SQLSrvConnection();
$connection
->setHost($_ENV['SQLSRV_HOST'])
->setPort((int)$_ENV['SQLSRV_PORT'])
->setDatabase($_ENV['SQLSRV_DATABASE'])
->setUser($_ENV['SQLSRV_USER'])
->setPassword($_ENV['SQLSRV_PASSWORD'])
->setCharset($_ENV['SQLSRV_CHARSET'])
->setOptions([
    SQLSrv::ATTR_PERSISTENT => true,
    SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
])
->setException(true)
->connect();
```

### Fluent Design

```php
// Create a new database connection using MySQLi engine in the fluent design format
$connection = SQLSrvConnection
::setHost($_ENV['SQLSRV_HOST'])
::setPort((int)$_ENV['SQLSRV_PORT'])
::setDatabase($_ENV['SQLSRV_DATABASE'])
::setUser($_ENV['SQLSRV_USER'])
::setPassword($_ENV['SQLSRV_PASSWORD'])
::setCharset($_ENV['SQLSRV_CHARSET'])
::setOptions([
    SQLSrv::ATTR_PERSISTENT => true,
    SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
])
::setException(true)
->connect();
```

### Static Arguments

```php
// Create a new database connection using MySQLi engine in the static arguments format
$connection = SQLSrvConnection::new(
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

### Static Array

```php
// Create a new database connection using MySQLi engine in the static array format
$connection = SQLSrvConnection::new([
    'host' => $_ENV['SQLSRV_HOST'],
    'port' => (int)$_ENV['SQLSRV_PORT'],
    'database' => $_ENV['SQLSRV_DATABASE'],
    'user' => $_ENV['SQLSRV_USER'],
    'password' => $_ENV['SQLSRV_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        SQLSrv::ATTR_PERSISTENT => true,
        SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    'exception' => true
])
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
