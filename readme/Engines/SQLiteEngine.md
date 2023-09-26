## Summary
The `SQLiteEngine` class is an implementation of the `IConnection` interface and provides functionality for connecting to and interacting with a SQLite database. It includes methods for connecting to the database, executing SQL statements, fetching data from the database, and managing transactions.

## Example Usage
```php
$engine = new SQLiteEngine();
$engine->setDatabase('path/to/database.sqlite');
$engine->connect();

$result = $engine->query('SELECT * FROM users');
$data = $result->fetchAll();

foreach ($data as $row) {
    echo $row['name'] . ' - ' . $row['email'] . PHP_EOL;
}

$engine->disconnect();
```

## Code Analysis
### Main functionalities
- Connect to a SQLite database
- Execute SQL statements
- Fetch data from the database
- Manage transactions
___
### Methods
- `__call(string $name, array $arguments): SQLiteEngine|string|int|bool|array|null`: Handles method calls for getting and setting properties dynamically.
- `__callStatic(string $name, array $arguments): SQLiteEngine`: Handles static method calls.
- `preConnect(): SQLiteEngine`: Prepares the connection options before connecting.
- `postConnect(): SQLiteEngine`: Updates the connection attributes after connecting.
- `realConnect(string $database, int $flags = null): SQLiteEngine`: Creates a new instance of the SQLiteEngine connection.
- `connect(): SQLiteEngine`: Establishes a database connection.
- `ping(): bool`: Pings the database server to check if the connection is still active.
- `disconnect(): void`: Disconnects from the database.
- `isConnected(): bool`: Checks if the connection is established.
- `parseDsn(): string|CustomException`: Parses the DSN from the DSN class.
- `getConnection(): mixed`: Gets the database connection instance.
- `setConnection(mixed $connection): mixed`: Sets the database connection instance.
- `loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null): int`: Imports an SQL dump from a file.
- `beginTransaction(): bool`: Starts a new transaction.
- `commit(): bool`: Commits the changes made during the transaction.
- `rollback(): bool`: Rolls back the changes made during the transaction.
- `inTransaction(): bool`: Checks if a transaction is currently active.
- `lastInsertId(?string $name = null): string|int|false`: Gets the last inserted ID.
- `quote(mixed ...$params): string|int`: Quotes a string for use in an SQL statement.
- `queryMetadata(): array`: Returns metadata about the last executed query.
- `queryString(): string`: Returns the last executed query string.
- `queryParameters(): array|null`: Returns the parameters of the last executed query.
- `queryRows(): int|false`: Returns the number of rows affected by the last executed query.
- `queryColumns(): int|false`: Returns the number of columns in the result of the last executed query.
- `affectedRows(): int|false`: Returns the number of affected rows by the last executed query.
- `bindParam(mixed ...$params): void`: Binds parameters to a prepared statement.
- `prepare(mixed ...$params): static|null`: Prepares an SQL statement for execution.
- `query(mixed ...$params): static|null`: Executes an SQL statement and returns the result set.
- `exec(mixed ...$params): mixed`: Executes an SQL statement and returns the number of affected rows.
- `fetch(int $fetchStyle = FETCH_BOTH, mixed $fetchArgument = null, mixed $optArgs = null): mixed`: Fetches the next row from the result set.
- `fetchAll(int $fetchStyle = FETCH_ASSOC, mixed $fetchArgument = null, mixed $optArgs = null): array`: Fetches all rows from the result set.
- `getAttribute(mixed $name): mixed`: Retrieves an attribute from the database.
- `setAttribute(mixed $name, mixed $value): void`: Sets an attribute for the database.
- `errorCode(mixed $inst = null): int|bool`: Returns the error code of the last operation.
- `errorInfo(mixed $inst = null): string|bool`: Returns the error information of the last operation.
___
### Fields
- `private static mixed $connection`: Instance of the database connection.
- `private static mixed $statement = null`: Instance of the database statement.
- `private static mixed $statementCount = null`: Instance of the database statement for counting rows.
- `private static mixed $statementResult = null`: Instance of the database statement for fetching results.
- `private ?int $queryRows = 0`: Number of rows affected by the last executed query.
- `private ?int $queryColumns = 0`: Number of columns in the result of the last executed query.
- `private ?int $affectedRows = 0`: Number of affected rows by the last executed query.
- `private string $queryString = ''`: Last executed query string.
- `private array $queryParameters = []`: Parameters of the last executed query.
___
