<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

use PDO;
use PDOException;
use Exception;

/**
 * Source reader for Microsoft SQL Server databases using the pdo_sqlsrv driver.
 *
 * Reads table structure and data from SQL Server via PDO.
 * Schema discovery uses INFORMATION_SCHEMA catalogue views.
 */
class SQLSrvReader extends BaseMigrator
{
    protected PDO $pdo;

    /**
     * @param string $host     SQL Server host (e.g. 'localhost' or '192.168.1.1')
     * @param string $user     SQL Server login
     * @param string $password Password
     * @param string $database Source database name
     * @param int    $port     Default 1433
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 1433
    ) {
        if (!extension_loaded('pdo_sqlsrv') && !extension_loaded('pdo_odbc')) {
            throw new Exception(
                'Neither pdo_sqlsrv nor pdo_odbc extension is available. '
                    . 'Please install a SQL Server PDO driver.'
            );
        }

        try {
            if (extension_loaded('pdo_sqlsrv')) {
                $dsn = "sqlsrv:Server={$host},{$port};Database={$database}";
            } else {
                // Fallback: use ODBC Driver via PDO over ODBC (pdo_odbc) (PDO with an ODBC DSN string)
                $dsn = "odbc:Driver={ODBC Driver 18 for SQL Server};Server={$host},{$port};Database={$database}";
            }

            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new Exception("SQL Server connection failed: " . $e->getMessage());
        }

        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string
    {
        return 'sqlsrv';
    }

    protected function loadTables(): void
    {
        $stmt = $this->pdo->query(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME"
        );
        if ($stmt) {
            $this->tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            // Column metadata
            $colStmt = $this->pdo->prepare(
                "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH,
                        NUMERIC_PRECISION, NUMERIC_SCALE, IS_NULLABLE, COLUMN_DEFAULT
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_NAME = ?
                 ORDER BY ORDINAL_POSITION"
            );
            $colStmt->execute([$table]);

            // Primary key columns
            $pkStmt = $this->pdo->prepare(
                "SELECT kcu.COLUMN_NAME
                 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                 JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                   ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                  AND tc.TABLE_NAME = kcu.TABLE_NAME
                 WHERE tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
                   AND tc.TABLE_NAME = ?
                 ORDER BY kcu.ORDINAL_POSITION"
            );
            $pkStmt->execute([$table]);
            $pkColumns = $pkStmt->fetchAll(PDO::FETCH_COLUMN);

            $columns    = [];
            $primaryKey = null;

            foreach ($colStmt->fetchAll() as $col) {
                $rawType = $col['DATA_TYPE'];
                $size    = $col['CHARACTER_MAXIMUM_LENGTH'];

                $displayType = strtoupper($rawType);
                if ($size === -1) {
                    $displayType .= '(MAX)';
                } elseif ($size !== null && $size > 0) {
                    $displayType .= "($size)";
                } elseif ($col['NUMERIC_PRECISION'] !== null) {
                    $displayType .= '(' . $col['NUMERIC_PRECISION']
                        . ($col['NUMERIC_SCALE'] !== null ? ',' . $col['NUMERIC_SCALE'] : '')
                        . ')';
                }

                $isPk = in_array($col['COLUMN_NAME'], $pkColumns, true);

                $columns[] = [
                    'name'       => $col['COLUMN_NAME'],
                    'raw_type'   => $displayType,
                    'canonical'  => $this->normalizeToCanonical($rawType),
                    'notnull'    => $col['IS_NULLABLE'] === 'NO',
                    'dflt_value' => $col['COLUMN_DEFAULT'],
                    'pk'         => $isPk,
                ];

                if ($isPk && $primaryKey === null) {
                    $primaryKey = $col['COLUMN_NAME'];
                }
            }

            $this->tableSchemas[$table] = [
                'columns'    => $columns,
                'primaryKey' => $primaryKey,
            ];
        }
    }

    public function getTableData(string $table): array
    {
        $stmt = $this->pdo->query("SELECT * FROM [$table]");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
