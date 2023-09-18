## Summary
The `MySQLiEngine` class is a implementation of the `IConnection` interface and provides functionality for connecting to a MySQL database using the MySQLi extension. It includes methods for establishing a connection, executing queries, fetching results, and managing transactions.

## Example Usage
```php
// Create an instance of the MySQLiEngine class
$engine = new MySQLiEngine();

// Set the connection options
$engine->setOptions([
  'host' => 'localhost',
  'user' => 'root',
  'password' => 'password',
  'database' => 'mydatabase',
  'port' => 3306
]);

// Connect to the database
$engine->connect();

// Execute a query
$engine->query('SELECT * FROM users');

// Fetch the results
$results = $engine->fetchAll();

// Close the connection
$engine->disconnect();
```

## Code Analysis
### Main functionalities
- Establishing a connection to a MySQL database
- Executing SQL queries
- Fetching and manipulating query results
- Managing transactions
___
### Methods
- `__call(string $name, array $arguments): MySQLiEngine|string|int|bool|array|null`: Handles method calls for setting and getting properties dynamically.
- `__callStatic(string $name, array $arguments): MySQLiEngine`: Handles static method calls.
- `preConnect(): MySQLiEngine`: Prepares the connection options before connecting.
- `postConnect(): MySQLiEngine`: Performs post-connection setup.
- `realConnect(string $host, string $user, string $password, string $database, mixed $port): MySQLiEngine`: Creates a new instance of the MySQLi connection and connects to the database.
- `connect(): MySQLiEngine`: Establishes a database connection.
- `ping(): bool`: Pings the database server to check the connection status.
- `disconnect(): void`: Disconnects from the database.
- `isConnected(): bool`: Checks if a connection is established.
- `parseDsn(): string|CustomException`: Parses the DSN from the DSN class.
- `getConnection(): mixed`: Gets the database connection instance.
- `setConnection(mixed $connection): mixed`: Sets the database connection instance.
- `loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null): int`: Imports an SQL dump from a file.
- `beginTransaction(): bool`: Starts a new transaction.
- `commit(): bool`: Commits the changes made during a transaction.
- `rollback(): bool`: Rolls back the changes made during a transaction.
- `inTransaction(): bool`: Checks if a transaction is currently active.
- `lastInsertId(?string $name = null): string|int|false`: Retrieves the last auto-increment ID generated during a transaction.
- `quote(mixed ...$params): mixed`: Quotes a string for use in an SQL statement.
- `queryMetadata(): array`: Returns metadata about the last executed query.
- `queryString(): string`: Returns the query string of the last executed query.
- `queryParameters(): array|null`: Returns the parameters of the last executed query.
- `queryRows(): int|false`: Returns the number of rows affected by the last executed query.
- `queryColumns(): int|false`: Returns the number of columns in the result of the last executed query.
- `affectedRows(): int|false`: Returns the number of rows affected by the last executed query.
- `bindParam(mixed ...$params): void`: Binds parameters to a prepared statement.
- `query(mixed ...$params): static|null`: Executes an SQL statement and returns the result set.
- `prepare(mixed ...$params): static|null`: Prepares an SQL statement for execution.
- `exec(mixed ...$params): mixed`: Executes an SQL statement and returns the number of affected rows.
- `fetch(int $fetchStyle = FETCH_BOTH, mixed $fetchArgument = null, mixed $optArgs = null): mixed`: Fetches the next row from the result set.
- `fetchAll(int $fetchStyle = FETCH_ASSOC, mixed $fetchArgument = null, mixed $optArgs = null): array`: Fetches all rows from the result set.
- `getAttribute(mixed $name): mixed`: Retrieves an attribute from the database.
- `setAttribute(mixed $name, mixed $value): void`: Sets an attribute on the database.
- `errorCode(mixed $inst = null): int|bool`: Retrieves the SQLSTATE code for the last operation.
- `errorInfo(mixed $inst = null): string|bool`: Retrieves error information for the last operation.
___
### Fields
- `private static mixed $connection`: Instance of the database connection.
- `private static mixed $statement = null`: Instance of the database statement.
- `private ?int $queryRows = 0`: Number of rows in the last executed query.
- `private ?int $queryColumns = 0`: Number of columns in the last executed query.
- `private ?int $affectedRows = 0`: Number of affected rows in the last executed query.
- `private string $queryString = ''`: Last executed query string.
- `private array $queryParameters = []`: Last executed query parameters.
___
