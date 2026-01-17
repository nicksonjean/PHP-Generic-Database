# PgSQLConnection

## PgSQLConnection Connection

The `GenericDatabase\PgSQLConnection` class is responsible for establishing and managing database connections. It uses a strategy pattern to support different database engines. The class provides methods for connecting to a database, executing queries, fetching results, and managing the connection state.

## Example Usage

### Load Class and Types Explicitly

```php
// Explicit and simplified module loading with all environment variables
use GenericDatabase\PgSQLConnection;
use GenericDatabase\Engine\PgSQL\PgSQL;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();
```

### Chainable Methods

```php
// Create a new database connection using MySQLi engine in the chainable methods format
$connection = new PgSQLConnection();
$connection
->setHost($_ENV['PGSQL_HOST'])
->setPort((int)$_ENV['PGSQL_PORT'])
->setDatabase($_ENV['PGSQL_DATABASE'])
->setUser($_ENV['PGSQL_USER'])
->setPassword($_ENV['PGSQL_PASSWORD'])
->setCharset($_ENV['PGSQL_CHARSET'])
->setOptions([
    PgSQL::ATTR_PERSISTENT => true,
    PgSQL::ATTR_CONNECT_ASYNC => true,
    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
])
->setException(true)
->connect();
```

### Fluent Design

```php
// Create a new database connection using MySQLi engine in the fluent design format
$connection = PgSQLConnection
::setHost($_ENV['PGSQL_HOST'])
::setPort((int)$_ENV['PGSQL_PORT'])
::setDatabase($_ENV['PGSQL_DATABASE'])
::setUser($_ENV['PGSQL_USER'])
::setPassword($_ENV['PGSQL_PASSWORD'])
::setCharset($_ENV['PGSQL_CHARSET'])
::setOptions([
    PgSQL::ATTR_PERSISTENT => true,
    PgSQL::ATTR_CONNECT_ASYNC => true,
    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
])
::setException(true)
->connect();
```

### Static Arguments

```php
// Create a new database connection using MySQLi engine in the static arguments format
$connection = PgSQLConnection::new(
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

### Static Array

```php
// Create a new database connection using MySQLi engine in the static array format
$connection = PgSQLConnection::new([
    'host' => $_ENV['PGSQL_HOST'],
    'port' => (int)$_ENV['PGSQL_PORT'],
    'database' => $_ENV['PGSQL_DATABASE'],
    'user' => $_ENV['PGSQL_USER'],
    'password' => $_ENV['PGSQL_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PgSQL::ATTR_PERSISTENT => true,
        PgSQL::ATTR_CONNECT_ASYNC => true,
        PgSQL::ATTR_CONNECT_FORCE_NEW => true,
        PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
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
