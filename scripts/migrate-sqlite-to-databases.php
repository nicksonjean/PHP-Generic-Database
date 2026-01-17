<?php

/**
 * Script para migrar banco de dados SQLite para MySQL, PostgreSQL, Oracle e SQL Server
 * 
 * Este script:
 * - Lê a estrutura e dados do SQLite em resources/database/sqlite/data/DB.SQLITE
 * - Cria o banco de dados "demodev" em cada banco de destino (se não existir)
 * - Cria todas as tabelas convertendo tipos de dados apropriadamente
 * - Migra todos os dados
 * 
 * Não altera o banco SQLite original e não faz nada com Firebird
 */

declare(strict_types=1);

use Dotenv\Dotenv;

define("PATH_ROOT", dirname(__DIR__, 1));

require_once PATH_ROOT . '/vendor/autoload.php';

// Carrega variáveis de ambiente
Dotenv::createImmutable(PATH_ROOT)->load();

// Caminho do banco SQLite
$sqlitePath = PATH_ROOT . '/resources/database/sqlite/data/DB.SQLITE';

if (!file_exists($sqlitePath)) {
    die("Erro: Banco SQLite não encontrado em: $sqlitePath\n");
}

echo "=== Migração de SQLite para MySQL, PostgreSQL, Oracle e SQL Server ===\n\n";

// Conecta ao SQLite (somente leitura)
try {
    $sqlite = new PDO('sqlite:' . $sqlitePath);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado ao SQLite\n";
} catch (PDOException $e) {
    die("Erro ao conectar ao SQLite: " . $e->getMessage() . "\n");
}

// Obtém todas as tabelas do SQLite
$tables = $sqlite->query(
    "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
)->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    die("Nenhuma tabela encontrada no banco SQLite.\n");
}

echo "Tabelas encontradas: " . count($tables) . "\n";
foreach ($tables as $table) {
    echo "  - $table\n";
}
echo "\n";

// Função para obter schema de uma tabela
function getTableSchema(PDO $sqlite, string $table): array
{
    $schema = [];
    $tableEscaped = str_replace('"', '""', $table);
    $columns = $sqlite->query("PRAGMA table_info(\"$tableEscaped\")")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        // SQLite pode retornar type vazio ou null, então usamos um default seguro
        $type = $col['type'] ?? '';
        if (empty(trim($type))) {
            // Se o tipo estiver vazio, usa TEXT como padrão (tipo mais genérico do SQLite)
            $type = 'TEXT';
        }
        
        $schema[] = [
            'name' => $col['name'],
            'type' => $type,
            'notnull' => (bool)$col['notnull'],
            'dflt_value' => $col['dflt_value'],
            'pk' => (bool)$col['pk']
        ];
    }
    
    return $schema;
}

// Função para converter tipo SQLite para MySQL
function sqliteToMySQLType(string $sqliteType): string
{
    $sqliteType = strtoupper(trim($sqliteType));
    
    if (strpos($sqliteType, 'INTEGER') !== false || strpos($sqliteType, 'INT') !== false) {
        if (strpos($sqliteType, 'UNSIGNED') !== false) {
            return 'INT UNSIGNED';
        }
        return 'INT';
    }
    
    if (strpos($sqliteType, 'VARCHAR') !== false) {
        return $sqliteType;
    }
    
    if (strpos($sqliteType, 'TEXT') !== false) {
        return 'TEXT';
    }
    
    if (strpos($sqliteType, 'DATETIME') !== false) {
        return 'DATETIME';
    }
    
    if (strpos($sqliteType, 'DATE') !== false) {
        return 'DATE';
    }
    
    if (strpos($sqliteType, 'TIME') !== false) {
        return 'TIME';
    }
    
    if (strpos($sqliteType, 'REAL') !== false || strpos($sqliteType, 'FLOAT') !== false || strpos($sqliteType, 'DOUBLE') !== false) {
        return 'DOUBLE';
    }
    
    if (strpos($sqliteType, 'BLOB') !== false) {
        return 'BLOB';
    }
    
    if (strpos($sqliteType, 'BOOLEAN') !== false || strpos($sqliteType, 'BOOL') !== false) {
        return 'BOOLEAN';
    }
    
    return 'TEXT'; // Default
}

// Função para converter tipo SQLite para PostgreSQL
function sqliteToPgSQLType(string $sqliteType): string
{
    $sqliteType = strtoupper(trim($sqliteType));
    
    if (strpos($sqliteType, 'INTEGER') !== false || strpos($sqliteType, 'INT') !== false) {
        if (strpos($sqliteType, 'UNSIGNED') !== false) {
            // PostgreSQL não tem unsigned, usamos INTEGER com CHECK
            return 'INTEGER';
        }
        return 'INTEGER';
    }
    
    if (strpos($sqliteType, 'VARCHAR') !== false) {
        return $sqliteType;
    }
    
    if (strpos($sqliteType, 'TEXT') !== false) {
        return 'TEXT';
    }
    
    if (strpos($sqliteType, 'DATETIME') !== false) {
        return 'TIMESTAMP';
    }
    
    if (strpos($sqliteType, 'DATE') !== false) {
        return 'DATE';
    }
    
    if (strpos($sqliteType, 'TIME') !== false) {
        return 'TIME';
    }
    
    if (strpos($sqliteType, 'REAL') !== false || strpos($sqliteType, 'FLOAT') !== false) {
        return 'REAL';
    }
    
    if (strpos($sqliteType, 'DOUBLE') !== false) {
        return 'DOUBLE PRECISION';
    }
    
    if (strpos($sqliteType, 'BLOB') !== false) {
        return 'BYTEA';
    }
    
    if (strpos($sqliteType, 'BOOLEAN') !== false || strpos($sqliteType, 'BOOL') !== false) {
        return 'BOOLEAN';
    }
    
    return 'TEXT'; // Default
}

// Função para converter tipo SQLite para Oracle
function sqliteToOracleType(string $sqliteType): string
{
    $sqliteType = strtoupper(trim($sqliteType));
    
    if (strpos($sqliteType, 'INTEGER') !== false || strpos($sqliteType, 'INT') !== false) {
        return 'NUMBER(10)';
    }
    
    if (strpos($sqliteType, 'VARCHAR') !== false) {
        // Extrai tamanho do VARCHAR
        if (preg_match('/VARCHAR\((\d+)\)/', $sqliteType, $matches)) {
            return 'VARCHAR2(' . $matches[1] . ')';
        }
        return 'VARCHAR2(255)';
    }
    
    if (strpos($sqliteType, 'TEXT') !== false) {
        return 'CLOB';
    }
    
    if (strpos($sqliteType, 'DATETIME') !== false) {
        return 'TIMESTAMP';
    }
    
    if (strpos($sqliteType, 'DATE') !== false) {
        return 'DATE';
    }
    
    if (strpos($sqliteType, 'TIME') !== false) {
        return 'TIMESTAMP';
    }
    
    if (strpos($sqliteType, 'REAL') !== false || strpos($sqliteType, 'FLOAT') !== false || strpos($sqliteType, 'DOUBLE') !== false) {
        return 'NUMBER';
    }
    
    if (strpos($sqliteType, 'BLOB') !== false) {
        return 'BLOB';
    }
    
    if (strpos($sqliteType, 'BOOLEAN') !== false || strpos($sqliteType, 'BOOL') !== false) {
        return 'NUMBER(1)';
    }
    
    return 'VARCHAR2(4000)'; // Default
}

// Função para converter tipo SQLite para SQL Server
function sqliteToSQLServerType(string $sqliteType): string
{
    $sqliteType = strtoupper(trim($sqliteType));
    
    if (strpos($sqliteType, 'INTEGER') !== false || strpos($sqliteType, 'INT') !== false) {
        if (strpos($sqliteType, 'UNSIGNED') !== false) {
            // SQL Server não tem unsigned nativo, mas podemos usar BIGINT ou INT
            return 'INT';
        }
        return 'INT';
    }
    
    if (strpos($sqliteType, 'VARCHAR') !== false) {
        return $sqliteType;
    }
    
    if (strpos($sqliteType, 'TEXT') !== false) {
        return 'NVARCHAR(MAX)';
    }
    
    if (strpos($sqliteType, 'DATETIME') !== false) {
        return 'DATETIME2';
    }
    
    if (strpos($sqliteType, 'DATE') !== false) {
        return 'DATE';
    }
    
    if (strpos($sqliteType, 'TIME') !== false) {
        return 'TIME';
    }
    
    if (strpos($sqliteType, 'REAL') !== false || strpos($sqliteType, 'FLOAT') !== false) {
        return 'FLOAT';
    }
    
    if (strpos($sqliteType, 'DOUBLE') !== false) {
        return 'FLOAT';
    }
    
    if (strpos($sqliteType, 'BLOB') !== false) {
        return 'VARBINARY(MAX)';
    }
    
    if (strpos($sqliteType, 'BOOLEAN') !== false || strpos($sqliteType, 'BOOL') !== false) {
        return 'BIT';
    }
    
    return 'NVARCHAR(MAX)'; // Default
}

// Função para escapar nome de tabela/coluna baseado no banco
function escapeIdentifier(string $name, string $dbType): string
{
    switch ($dbType) {
        case 'mysql':
            return "`$name`";
        case 'sqlsrv':
            // SQL Server usa colchetes [ ] para delimitadores
            return "[$name]";
        case 'pgsql':
        case 'oracle':
            return "\"$name\"";
        default:
            return $name;
    }
}

// Função para gerar CREATE TABLE SQL
function generateCreateTableSQL(string $table, array $schema, string $dbType): string
{
    $tableEscaped = escapeIdentifier($table, $dbType);
    
    // Oracle e SQL Server não suportam IF NOT EXISTS nativamente
    if ($dbType === 'oracle') {
        $sql = "CREATE TABLE $tableEscaped (\n";
    } elseif ($dbType === 'sqlsrv') {
        $sql = "CREATE TABLE $tableEscaped (\n";
    } elseif ($dbType === 'pgsql') {
        $sql = "CREATE TABLE IF NOT EXISTS $tableEscaped (\n";
    } else {
        $sql = "CREATE TABLE IF NOT EXISTS $tableEscaped (\n";
    }
    
    $columns = [];
    $primaryKeys = [];
    
    foreach ($schema as $col) {
        $colName = $col['name'];
        $colNameEscaped = escapeIdentifier($colName, $dbType);
        
        // Garante que temos um tipo válido
        $sqliteType = $col['type'] ?? 'TEXT';
        if (empty(trim($sqliteType))) {
            $sqliteType = 'TEXT';
        }
        
        $colType = '';
        
        switch ($dbType) {
            case 'mysql':
                $colType = sqliteToMySQLType($sqliteType);
                break;
            case 'pgsql':
                $colType = sqliteToPgSQLType($sqliteType);
                break;
            case 'oracle':
                $colType = sqliteToOracleType($sqliteType);
                break;
            case 'sqlsrv':
                $colType = sqliteToSQLServerType($sqliteType);
                break;
        }
        
        // Garante que temos um tipo válido após conversão
        if (empty(trim($colType))) {
            $colType = $dbType === 'mysql' ? 'TEXT' : ($dbType === 'sqlsrv' ? 'NVARCHAR(MAX)' : 'TEXT');
        }
        
        $columnDef = "$colNameEscaped $colType";
        
        if ($col['notnull']) {
            $columnDef .= " NOT NULL";
        }
        
        if ($col['dflt_value'] !== null) {
            $default = $col['dflt_value'];
            // Remove aspas se existirem
            if (!empty($default) && (($default[0] === "'" && substr($default, -1) === "'") || ($default[0] === '"' && substr($default, -1) === '"'))) {
                $default = substr($default, 1, -1);
            }
            
            // Para Oracle, não escapamos aqui pois faremos escape geral depois
            // Para outros bancos, escapamos normalmente
            if ($dbType !== 'oracle') {
                $default = str_replace("'", "''", $default);
            }
            
            // Trata valores numéricos
            if (is_numeric($default) && ($dbType === 'oracle' || strpos($colType, 'NUMBER') !== false || strpos($colType, 'INT') !== false)) {
                $columnDef .= " DEFAULT $default";
            } elseif ($dbType === 'oracle' && strpos($colType, 'NUMBER') === false) {
                $columnDef .= " DEFAULT '$default'";
            } elseif ($dbType === 'sqlsrv' && (strpos($colType, 'INT') === false && strpos($colType, 'BIT') === false && strpos($colType, 'FLOAT') === false && strpos($colType, 'REAL') === false)) {
                $columnDef .= " DEFAULT '$default'";
            } elseif ($dbType !== 'oracle' && $dbType !== 'sqlsrv') {
                $columnDef .= " DEFAULT '$default'";
            }
        }
        
        $columns[] = $columnDef;
        
        if ($col['pk']) {
            $primaryKeys[] = $colName;
        }
    }
    
    $sql .= "  " . implode(",\n  ", $columns);
    
    if (!empty($primaryKeys)) {
        $pkCols = array_map(fn($col) => escapeIdentifier($col, $dbType), $primaryKeys);
        $sql .= ",\n  PRIMARY KEY (" . implode(", ", $pkCols) . ")";
    }
    
    if ($dbType === 'mysql') {
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    } elseif ($dbType === 'oracle') {
        // Oracle: fecha o parêntese primeiro
        $sql .= "\n)";
        
        // Para Oracle, precisamos escapar aspas simples dentro do EXECUTE IMMEDIATE
        // Escapa todas as aspas simples duplicando-as (escape padrão do Oracle)
        $escapedSQL = str_replace("'", "''", $sql);
        
        // Reconstrói o SQL dentro do bloco PL/SQL com tratamento de erro
        $sql = "BEGIN\n";
        $sql .= "  EXECUTE IMMEDIATE '" . $escapedSQL . "';\n";
        $sql .= "EXCEPTION\n";
        $sql .= "  WHEN OTHERS THEN\n";
        $sql .= "    IF SQLCODE = -955 THEN NULL; -- Table already exists\n";
        $sql .= "    ELSE RAISE;\n";
        $sql .= "    END IF;\n";
        $sql .= "END;";
    } else {
        $sql .= "\n)";
    }
    
    return $sql;
}

// Função para migrar dados de uma tabela
function migrateTableData(PDO $source, PDO $target, string $table, string $dbType, array $schema = []): int
{
    try {
        $tableEscaped = escapeIdentifier($table, $dbType);
        
        // Obtém todos os dados da tabela (SQLite aceita nomes de tabela com aspas duplas ou sem aspas)
        $rows = $source->query('SELECT * FROM "' . str_replace('"', '""', $table) . '"')->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            return 0;
        }
        
        // Obtém nomes das colunas
        $columns = array_keys($rows[0]);
        
        // Cria um mapa de coluna para tipo (para Oracle)
        $columnTypes = [];
        if (!empty($schema)) {
            foreach ($schema as $col) {
                $columnTypes[$col['name']] = strtoupper(trim($col['type']));
            }
        }
        
        // Para Oracle, precisamos usar TO_DATE/TO_TIMESTAMP para campos de data
        if ($dbType === 'oracle') {
            $columnList = [];
            $placeholders = [];
            
            foreach ($columns as $col) {
                $colEscaped = escapeIdentifier($col, $dbType);
                $colType = $columnTypes[$col] ?? '';
                
                // Se for campo de data/timestamp, usa função de conversão
                if (strpos($colType, 'DATE') !== false || strpos($colType, 'TIMESTAMP') !== false || strpos($colType, 'TIME') !== false) {
                    $placeholders[] = "TO_TIMESTAMP(?, 'YYYY-MM-DD HH24:MI:SS')";
                } elseif (strpos($colType, 'DATE') !== false && strpos($colType, 'TIME') === false) {
                    $placeholders[] = "TO_DATE(?, 'YYYY-MM-DD')";
                } else {
                    $placeholders[] = "?";
                }
                
                $columnList[] = $colEscaped;
            }
            
            $columnListStr = implode(", ", $columnList);
            $placeholdersStr = implode(", ", $placeholders);
            $insertSQL = "INSERT INTO $tableEscaped ($columnListStr) VALUES ($placeholdersStr)";
        } else {
            $columnList = implode(", ", array_map(fn($col) => escapeIdentifier($col, $dbType), $columns));
            $placeholders = implode(", ", array_fill(0, count($columns), "?"));
            $insertSQL = "INSERT INTO $tableEscaped ($columnList) VALUES ($placeholders)";
        }
        
        $stmt = $target->prepare($insertSQL);
        
        $count = 0;
        foreach ($rows as $row) {
            $values = array_values($row);
            
            // Para Oracle, converte datas para formato padrão e garante UTF-8
            if ($dbType === 'oracle') {
                foreach ($columns as $i => $colName) {
                    $colType = $columnTypes[$colName] ?? '';
                    
                    if ($values[$i] !== null && !empty($values[$i])) {
                        // Garante que strings estejam em UTF-8
                        if (is_string($values[$i])) {
                            // Converte para UTF-8 se não estiver
                            if (!mb_check_encoding($values[$i], 'UTF-8')) {
                                $values[$i] = mb_convert_encoding($values[$i], 'UTF-8', 'auto');
                            }
                            // Remove caracteres de controle inválidos
                            $values[$i] = mb_convert_encoding($values[$i], 'UTF-8', 'UTF-8');
                        }
                        
                        // Se for campo de data/timestamp
                        if (strpos($colType, 'DATE') !== false || strpos($colType, 'TIMESTAMP') !== false || strpos($colType, 'TIME') !== false) {
                            // Tenta converter para formato padrão Oracle
                            $dateValue = $values[$i];
                            
                            // Se for string, tenta parsear
                            if (is_string($dateValue) && !empty(trim($dateValue))) {
                                $dateValue = trim($dateValue);
                                
                                // Tenta parsear como timestamp do SQLite (YYYY-MM-DD HH:MM:SS ou YYYY-MM-DD)
                                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{2}):(\d{2}):(\d{2}))?/', $dateValue, $matches)) {
                                    // Valida a data
                                    if (checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
                                        // Já está no formato correto ou parcialmente
                                        if (count($matches) === 4 || !isset($matches[4])) {
                                            // Apenas data, adiciona hora
                                            $dateValue = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' 00:00:00';
                                        } else {
                                            // Tem hora, completa se necessário
                                            $hour = str_pad($matches[4] ?? '00', 2, '0', STR_PAD_LEFT);
                                            $min = str_pad($matches[5] ?? '00', 2, '0', STR_PAD_LEFT);
                                            $sec = str_pad($matches[6] ?? '00', 2, '0', STR_PAD_LEFT);
                                            $dateValue = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $hour . ':' . $min . ':' . $sec;
                                        }
                                        $values[$i] = $dateValue;
                                    } else {
                                        // Data inválida, tenta strtotime como fallback
                                        $timestamp = strtotime($dateValue);
                                        if ($timestamp !== false) {
                                            $values[$i] = date('Y-m-d H:i:s', $timestamp);
                                        }
                                    }
                                } else {
                                    // Tenta converter usando strtotime
                                    $timestamp = @strtotime($dateValue);
                                    if ($timestamp !== false) {
                                        $values[$i] = date('Y-m-d H:i:s', $timestamp);
                                    } else {
                                        // Se não conseguir converter, deixa como string e deixa o Oracle tentar
                                        // ou converte para NULL se vazio
                                        if (empty($dateValue)) {
                                            $values[$i] = null;
                                        }
                                    }
                                }
                            } elseif (is_numeric($dateValue) && $dateValue > 0) {
                                // Se for timestamp Unix
                                $values[$i] = date('Y-m-d H:i:s', (int)$dateValue);
                            } elseif (empty($dateValue)) {
                                // Valor vazio
                                $values[$i] = null;
                            }
                        } else {
                            // Para campos não-data, garante UTF-8
                            if (is_string($values[$i])) {
                                // Converte para UTF-8 se não estiver
                                if (!mb_check_encoding($values[$i], 'UTF-8')) {
                                    $values[$i] = mb_convert_encoding($values[$i], 'UTF-8', 'auto');
                                }
                            }
                        }
                    }
                }
            }
            
            // Converte valores NULL adequadamente e garante UTF-8 para todos os strings
            foreach ($values as $i => $value) {
                if ($value === null || $value === '') {
                    $values[$i] = null;
                } elseif (is_string($value) && $dbType === 'oracle') {
                    // Garante UTF-8 para Oracle
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $values[$i] = mb_convert_encoding($value, 'UTF-8', 'auto');
                    }
                }
            }
            
            $stmt->execute($values);
            $count++;
        }
        
        return $count;
    } catch (PDOException $e) {
        throw new Exception("Erro ao migrar dados da tabela $table: " . $e->getMessage());
    }
}

// Função para processar banco de dados específico
function processDatabase(
    PDO $sqlite,
    array $tables,
    string $dbType,
    string $dbName,
    callable $createDbFn,
    callable $connectFn
): void {
    // Exibe nome amigável do banco
    $dbTypeDisplay = ucfirst($dbType);
    if ($dbType === 'pgsql') {
        $dbTypeDisplay = 'PostgreSQL';
    } elseif ($dbType === 'sqlsrv') {
        $dbTypeDisplay = 'SQL Server';
    }
    echo "\n=== Processando $dbTypeDisplay ===\n";
    
    try {
        // Cria banco de dados se não existir
        echo "Criando banco de dados '$dbName'...\n";
        $createDbFn($dbName);
        echo "✓ Banco de dados criado/verificado\n";
        
        // Conecta ao banco
        echo "Conectando ao banco...\n";
        $conn = $connectFn($dbName);
        echo "✓ Conectado\n";
        
        // Processa cada tabela
        foreach ($tables as $table) {
            echo "\nProcessando tabela: $table\n";
            
            // Obtém schema
            $schema = getTableSchema($sqlite, $table);
            
            // Normaliza o tipo de banco para minúsculas para uso interno
            $dbTypeNormalized = strtolower($dbType);
            
            // Verifica se a tabela já existe (para SQL Server e Oracle)
            $tableExists = false;
            if ($dbTypeNormalized === 'sqlsrv') {
                try {
                    $tableEscaped = escapeIdentifier($table, $dbTypeNormalized);
                    $checkSQL = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$table'";
                    $result = $conn->query($checkSQL)->fetchColumn();
                    $tableExists = ($result > 0);
                } catch (PDOException $e) {
                    // Se der erro, assume que não existe
                    $tableExists = false;
                }
            } elseif ($dbTypeNormalized === 'oracle') {
                try {
                    $tableEscaped = escapeIdentifier($table, $dbTypeNormalized);
                    $checkSQL = "SELECT COUNT(*) FROM USER_TABLES WHERE TABLE_NAME = UPPER('$table')";
                    $result = $conn->query($checkSQL)->fetchColumn();
                    $tableExists = ($result > 0);
                } catch (PDOException $e) {
                    // Se der erro, assume que não existe
                    $tableExists = false;
                }
            }
            
            if ($tableExists) {
                echo "  ! Tabela já existe, pulando criação\n";
            } else {
                // Gera SQL CREATE TABLE
                $createSQL = generateCreateTableSQL($table, $schema, $dbTypeNormalized);
                
                // Cria tabela
                try {
                    if ($dbTypeNormalized === 'oracle') {
                        // Oracle precisa de tratamento especial devido ao PL/SQL block
                        $conn->exec($createSQL);
                    } else {
                        $conn->exec($createSQL);
                    }
                    echo "  ✓ Tabela criada\n";
                } catch (PDOException $e) {
                    $errorMsg = $e->getMessage();
                    if (strpos($errorMsg, 'already exists') !== false || 
                        strpos($errorMsg, 'ORA-00955') !== false ||
                        strpos($errorMsg, 'ORA-00942') !== false ||
                        strpos($errorMsg, 'There is already an object') !== false ||
                        (strpos($errorMsg, 'Table') !== false && strpos($errorMsg, 'exists') !== false) ||
                        (strpos($errorMsg, 'table') !== false && strpos($errorMsg, 'exists') !== false) ||
                        strpos($errorMsg, 'Duplicate table') !== false ||
                        strpos($errorMsg, 'duplicate key') !== false) {
                        echo "  ! Tabela já existe, pulando criação\n";
                    } else {
                        echo "  ✗ Erro ao criar tabela: " . $errorMsg . "\n";
                        echo "  SQL: " . substr($createSQL, 0, 200) . "...\n";
                        throw $e;
                    }
                }
            }
            
            // Verifica se a tabela já tem dados
            $tableEscaped = escapeIdentifier($table, $dbTypeNormalized);
            $existingCount = $conn->query("SELECT COUNT(*) FROM $tableEscaped")->fetchColumn();
            if ($existingCount > 0) {
                echo "  ! Tabela já possui $existingCount registros, pulando migração de dados\n";
                continue;
            }
            
            // Migra dados
            try {
                $count = migrateTableData($sqlite, $conn, $table, $dbTypeNormalized, $schema);
                echo "  ✓ $count registros migrados\n";
            } catch (Exception $e) {
                echo "  ✗ Erro ao migrar dados: " . $e->getMessage() . "\n";
                // Continua com próxima tabela
            }
        }
        
        echo "\n✓ Migração para $dbType concluída com sucesso!\n";
        
    } catch (Exception $e) {
        echo "✗ Erro ao processar $dbType: " . $e->getMessage() . "\n";
        echo "  Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

// Processa MySQL
if (isset($_ENV['MYSQL_PORT'])) {
    $mysqlHost = 'localhost';
    $mysqlPort = $_ENV['MYSQL_PORT'];
    $mysqlUser = $_ENV['MYSQL_USERNAME'] ?? 'root';
    $mysqlPass = $_ENV['MYSQL_PASSWORD'] ?? '';
    
    $createMySQLDb = function($dbName) use ($mysqlHost, $mysqlPort, $mysqlUser, $mysqlPass) {
        try {
            $tempConn = new PDO(
                "mysql:host=$mysqlHost;port=$mysqlPort;charset=utf8mb4",
                $mysqlUser,
                $mysqlPass
            );
            $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $tempConn->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'database exists') === false) {
                throw $e;
            }
        }
    };
    
    $connectMySQL = function($dbName) use ($mysqlHost, $mysqlPort, $mysqlUser, $mysqlPass) {
        $conn = new PDO(
            "mysql:host=$mysqlHost;port=$mysqlPort;dbname=$dbName;charset=utf8mb4",
            $mysqlUser,
            $mysqlPass
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    };
    
    processDatabase($sqlite, $tables, 'mysql', 'demodev', $createMySQLDb, $connectMySQL);
}

// Processa PostgreSQL
if (isset($_ENV['PGSQL_PORT'])) {
    $pgsqlHost = 'localhost';
    $pgsqlPort = $_ENV['PGSQL_PORT'];
    $pgsqlUser = $_ENV['PGSQL_USERNAME'] ?? $_ENV['PGSQL_USER'] ?? 'postgres';
    $pgsqlPass = $_ENV['PGSQL_PASSWORD'] ?? '';
    
    $createPgSQLDb = function($dbName) use ($pgsqlHost, $pgsqlPort, $pgsqlUser, $pgsqlPass) {
        try {
            $tempConn = new PDO(
                "pgsql:host=$pgsqlHost;port=$pgsqlPort",
                $pgsqlUser,
                $pgsqlPass
            );
            $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $tempConn->query("SELECT 1 FROM pg_database WHERE datname = '$dbName'");
            $exists = $stmt->fetchColumn();
            if (!$exists) {
                $tempConn->exec("CREATE DATABASE \"$dbName\"");
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    };
    
    $connectPgSQL = function($dbName) use ($pgsqlHost, $pgsqlPort, $pgsqlUser, $pgsqlPass) {
        $conn = new PDO(
            "pgsql:host=$pgsqlHost;port=$pgsqlPort;dbname=$dbName",
            $pgsqlUser,
            $pgsqlPass
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    };
    
    processDatabase($sqlite, $tables, 'pgsql', 'demodev', $createPgSQLDb, $connectPgSQL);
}

// Processa Oracle
if (isset($_ENV['OCI_PORT'])) {
    $ociHost = 'localhost';
    $ociPort = $_ENV['OCI_PORT'];
    $ociDatabase = $_ENV['OCI_DATABASE'] ?? 'FREE';
    $ociUser = $_ENV['OCI_USERNAME'] ?? $_ENV['OCI_USER'] ?? 'hr';
    $ociPass = $_ENV['OCI_PASSWORD'] ?? '';
    
    $createOracleDb = function($dbName) {
        // Para Oracle, não criamos um novo database, mas podemos criar um schema/user
        // Por enquanto, apenas verificamos a conexão
        // Oracle Free usa um service name, não múltiplos databases
    };
    
    $connectOracle = function($dbName) use ($ociHost, $ociPort, $ociDatabase, $ociUser, $ociPass) {
        // Oracle usa service name ou SID
        // Tenta primeiro com formato de service name
        $serviceName = $ociDatabase;
        
        // Se OCI_DATABASE contém apenas o nome do serviço, usa TNS format
        // Caso contrário, assume que é um connection string completo
        if (strpos($serviceName, '=') === false) {
            // Formato simples - assume service name
            $connString = "//$ociHost:$ociPort/$serviceName";
        } else {
            // Formato TNS completo
            $connString = $serviceName;
        }
        
        try {
            // Tenta conexão com charset UTF-8
            $conn = new PDO("oci:dbname=$connString;charset=AL32UTF8", $ociUser, $ociPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            // Tenta formato alternativo com charset
            $connString = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$ociHost)(PORT=$ociPort))(CONNECT_DATA=(SERVICE_NAME=$serviceName)))";
            try {
                $conn = new PDO("oci:dbname=$connString;charset=AL32UTF8", $ociUser, $ociPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e2) {
                // Última tentativa sem charset explícito (driver pode não suportar)
                $conn = new PDO("oci:dbname=$connString", $ociUser, $ociPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
        }
        
        // Define o charset do ambiente Oracle para UTF-8
        try {
            $conn->exec("ALTER SESSION SET NLS_CHARACTERSET = 'AL32UTF8'");
            $conn->exec("ALTER SESSION SET NLS_NCHAR_CHARACTERSET = 'AL16UTF16'");
            $conn->exec("ALTER SESSION SET NLS_LANGUAGE = 'AMERICAN'");
            $conn->exec("ALTER SESSION SET NLS_TERRITORY = 'AMERICA'");
        } catch (PDOException $e) {
            // Ignora erros ao definir charset (pode não ter permissão ou não ser necessário)
        }
        
        return $conn;
    };
    
    processDatabase($sqlite, $tables, 'oracle', 'demodev', $createOracleDb, $connectOracle);
}

// Processa SQL Server
if ((isset($_ENV['SQLSRV_PORT']) || isset($_ENV['SQLSERVER_PORT'])) && extension_loaded('pdo_sqlsrv')) {
    $sqlsrvHost = 'localhost';
    $sqlsrvPort = $_ENV['SQLSRV_PORT'] ?? $_ENV['SQLSERVER_PORT'] ?? '1433';
    $sqlsrvUser = $_ENV['SQLSRV_USERNAME'] ?? $_ENV['SQLSERVER_USER'] ?? 'sa';
    $sqlsrvPass = $_ENV['SQLSRV_PASSWORD'] ?? $_ENV['SQLSERVER_PASSWORD'] ?? '';
    
    $createSQLSrvDb = function($dbName) use ($sqlsrvHost, $sqlsrvPort, $sqlsrvUser, $sqlsrvPass) {
        try {
            $tempConn = new PDO(
                "sqlsrv:Server=$sqlsrvHost,$sqlsrvPort",
                $sqlsrvUser,
                $sqlsrvPass
            );
            $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $tempConn->exec("IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = '$dbName') CREATE DATABASE [$dbName]");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    };
    
    $connectSQLSrv = function($dbName) use ($sqlsrvHost, $sqlsrvPort, $sqlsrvUser, $sqlsrvPass) {
        $conn = new PDO(
            "sqlsrv:Server=$sqlsrvHost,$sqlsrvPort;Database=$dbName",
            $sqlsrvUser,
            $sqlsrvPass
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    };
    
    try {
        processDatabase($sqlite, $tables, 'sqlsrv', 'demodev', $createSQLSrvDb, $connectSQLSrv);
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'ODBC Driver') !== false || strpos($errorMsg, 'IMSSP') !== false) {
            echo "\n=== SQL Server ===\n";
            echo "! Driver ODBC compatível não está instalado.\n";
            echo "  A extensão PDO SQL Server requer especificamente o ODBC Driver 17 ou 18 for SQL Server.\n";
            echo "  Um driver ODBC antigo pode estar instalado, mas não é compatível.\n\n";
            echo "  Para instalar:\n";
            echo "  - Execute o script: .\\scripts\\verify-and-install-dev-tools.ps1 (como Administrador)\n";
            echo "  - Ou instale manualmente: https://go.microsoft.com/fwlink/?LinkId=163712\n";
            echo "  - Ou via Winget: winget install Microsoft.ODBC.Driver.18.for.SQL.Server\n\n";
            echo "  IMPORTANTE: Após instalar, REINICIE o servidor web/PHP.\n";
        } else {
            throw $e;
        }
    } catch (Exception $e) {
        // Re-lança outras exceções para serem tratadas pelo processDatabase
        throw $e;
    }
} elseif (isset($_ENV['SQLSRV_PORT']) || isset($_ENV['SQLSERVER_PORT'])) {
    echo "\n=== SQL Server ===\n";
    echo "! Extensão PDO SQL Server não está instalada. Pulando SQL Server.\n";
    echo "  Para instalar no Windows, baixe os drivers da Microsoft e habilite pdo_sqlsrv no php.ini\n";
}

echo "\n=== Migração concluída! ===\n";
