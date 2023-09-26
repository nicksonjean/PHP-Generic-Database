## Summary
This code defines the `FBirdEngine` class, which is responsible for connecting to and interacting with a Firebird database. It includes methods for establishing a connection, executing queries, fetching results, and managing transactions.

## Example Usage
```php
// Create an instance of the FBirdEngine class
$engine = new FBirdEngine();

// Set the connection parameters
$engine->setHost('localhost');
$engine->setUser('username');
$engine->setPassword('password');
$engine->setDatabase('database');
$engine->setPort(3050);

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
- Establishing a connection to a Firebird database
- Executing SQL queries and fetching results
- Managing transactions (begin, commit, rollback)
- Importing SQL dumps from files
- Retrieving metadata about executed queries (number of rows, columns, affected rows)
- Binding parameters to prepared statements
- Handling errors and retrieving error information
___
### Methods
- `connect()`: Establishes a connection to the database.
- `disconnect()`: Closes the connection to the database.
- `query($sql)`: Executes an SQL query and returns the result set.
- `fetchAll($fetchStyle)`: Fetches all rows from the result set and returns them as an array.
- `beginTransaction()`: Starts a new transaction.
- `commit()`: Commits the changes made during the transaction.
- `rollback()`: Rolls back the changes made during the transaction.
- `loadFromFile($file)`: Imports an SQL dump from a file.
- `queryMetadata()`: Returns metadata about the last executed query.
- `bindParam($params)`: Binds parameters to a prepared statement.
- `errorCode()`: Returns the SQLSTATE code for the last operation.
- `errorInfo()`: Returns an array with error information about the last operation.
___
### Fields
- `$connection`: Instance of the connection with the database.
- `$statement`: Instance of the statement of the database.
- `$statementCount`: Instance of the statement of the database for counting rows.
- `$statementResult`: Instance of the statement of the database for fetching results.
- `$queryRows`: Number of rows in the last executed query.
- `$queryColumns`: Number of columns in the last executed query.
- `$affectedRows`: Number of affected rows in the last executed query.
- `$queryString`: Last executed SQL query.
- `$queryParameters`: Last executed query parameters.
___
