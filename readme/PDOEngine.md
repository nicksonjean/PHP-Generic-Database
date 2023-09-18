## Summary
The `PDOEngine` class is a database engine implementation that uses the PDO extension in PHP. It provides methods for connecting to a database, executing SQL statements, and fetching results. The class also includes functionality for managing transactions, binding parameters, and retrieving metadata about executed queries.

## Example Usage
```php
// Create an instance of the PDOEngine class
$engine = new PDOEngine();

// Connect to a database
$engine->connect();

// Execute a query and fetch results
$engine->query('SELECT * FROM users');
$results = $engine->fetchAll();

// Bind parameters and execute a prepared statement
$engine->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
$engine->bindParam('John Doe', 'john@example.com');
$engine->exec();

// Manage transactions
$engine->beginTransaction();
$engine->exec('UPDATE users SET name = "Jane Doe" WHERE id = 1');
$engine->commit();

// Disconnect from the database
$engine->disconnect();
```

## Code Analysis
### Main functionalities
- Connect to a database using PDO
- Execute SQL statements and fetch results
- Manage transactions
- Bind parameters to prepared statements
- Retrieve metadata about executed queries
___
### Methods
- `__call(string $name, array $arguments): PDOEngine|string|int|bool|array|null`: Handles method calls for getting and setting properties dynamically.
- `__callStatic(string $name, array $arguments): PDOEngine`: Handles static method calls.
- `preConnect(): PDOEngine`: Prepares connection options before connecting.
- `postConnect(): PDOEngine`: Updates connection attributes after connecting.
- `realConnect(string $dsn, ?string $user = null, ?string $password = null, ?array $options = null): PDOEngine`: Creates a new PDO connection instance.
- `connect(): PDOEngine`: Establishes a database connection.
- `ping(): bool`: Pings the database server to check the connection.
- `disconnect(): void`: Disconnects from the database.
- `isConnected(): bool`: Checks if a connection is established.
- `parseDsn(): string|Exception`: Parses the DSN from the DSN class.
- `getConnection(): mixed`: Gets the database connection instance.
- `setConnection(mixed $connection): mixed`: Sets the database connection instance.
- `loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null): int`: Imports an SQL dump from a file.
- `beginTransaction(): bool`: Starts a new transaction.
- `commit(): bool`: Commits changes made during a transaction.
- `rollback(): bool`: Rolls back changes made during a transaction.
- `inTransaction(): bool`: Checks if a transaction is active.
- `lastInsertId(?string $name = null): string|int|false`: Gets the last inserted ID.
- `quote(mixed ...$params): mixed`: Quotes a string for use in an SQL statement.
- `queryMetadata(): array`: Returns metadata about the last executed query.
- `queryString(): string`: Returns the last executed query string.
- `queryParameters(): array|null`: Returns the parameters of the last executed query.
- `queryRows(): int|false`: Returns the number of rows affected by the last executed query.
- `queryColumns(): int|false`: Returns the number of columns in the result of the last executed query.
- `affectedRows(): int|false`: Returns the number of affected rows by the last executed query.
- `bindParam(mixed ...$params): void`: Binds parameters to a prepared statement.
- `query(mixed ...$params): static|null`: Executes an SQL statement and returns the result set.
- `prepare(mixed ...$params): static|null`: Prepares an SQL statement for execution.
- `exec(mixed ...$params): bool`: Executes an SQL statement and returns the number of affected rows.
- `fetch(int $fetchStyle = FETCH_BOTH, mixed $fetchArgument = null, mixed $optArgs = null): mixed`: Fetches the next row from the result set.
- `fetchAll(int $fetchStyle = FETCH_ASSOC, mixed $fetchArgument = null, mixed $optArgs = null): array`: Fetches all rows from the result set.
- `getAttribute(mixed $name): mixed`: Retrieves an attribute from the database.
- `setAttribute(mixed $name, mixed $value): void`: Sets an attribute on the database.
- `errorCode(mixed $inst = null): int|bool`: Returns the SQLSTATE code for the last operation.
- `errorInfo(mixed $inst = null): string|bool`: Returns error information about the last operation.
___
### Fields
- `private static mixed $connection`: Instance of the database connection.
- `private static mixed $statement = null`: Instance of the database statement.
- `private static mixed $statementCount = null`: Instance of the database statement for counting rows.
- `private ?int $queryRows = 0`: Number of rows in the last executed query.
- `private ?int $queryColumns = 0`: Number of columns in the last executed query.
- `private ?int $affectedRows = 0`: Number of affected rows by the last executed query.
- `private string $queryString = ''`: Last executed query string.
- `private array $queryParameters = []`: Last executed query parameters.
___
