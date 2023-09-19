# Connection

## Connection Strategy

The `GenericDatabase\Connection` class is responsible for establishing and managing database connections. It uses a strategy pattern to support different database engines. The class provides methods for connecting to a database, executing queries, fetching results, and managing the connection state.

## Example Usage

```php
// Create a new database connection using PDO engine
$connection = Connection::new('pdo', 'mysql', 'localhost', 'root', 'password');

// Connect to the database
$connection->connect();

// Execute a query
$result = $connection->query('SELECT * FROM users');

// Fetch all rows from the result set
$rows = $connection->fetchAll($result);

// Disconnect from the database
$connection->disconnect();
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
- `__call(string $name, array $arguments): Connection`: Handles dynamic method calls and delegates them to the strategy instance.
- `__callStatic(string $name, array $arguments): Connection`: Handles static method calls and delegates them to the instance or strategy instance.

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
- `initFactory(mixed $params): void`: Initializes the strategy instance based on the engine parameter.
- `call(mixed $instance, mixed $name, mixed $arguments): Connection`: Calls a method on an instance and returns the connection instance.
- `callWithByStaticArray(array $arguments): Connection`: Calls multiple setter methods on the instance using an array of arguments.
- `callWithByStaticArgs(array $arguments): Connection`: Calls setter methods on the instance using individual arguments.
- `callArgumentsByFormat(string $format, mixed $arguments): Connection`: Calls methods based on the data format (json, xml, ini, yaml) and initializes the strategy instance.

### Fields

- `engineList`: An array of supported database engines.
- `strategy`: The strategy instance for the database connection.
