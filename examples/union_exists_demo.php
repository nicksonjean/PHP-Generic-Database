<?php

require_once __DIR__ . '/vendor/autoload.php';

use GenericDatabase\Connection;
use GenericDatabase\QueryBuilder;

// Exemplo de uso das novas funcionalidades: UNION, UNION ALL e EXISTS

echo "=== Demonstração de UNION, UNION ALL e EXISTS ===\n\n";

// Conexão com banco de dados (exemplo com SQLite)
$connection = Connection::sqlite()->setDatabase(':memory:')->connect();

// Criar tabelas de exemplo
$connection->query("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        name TEXT,
        city TEXT,
        salary REAL
    )
");

$connection->query("
    CREATE TABLE employees (
        id INTEGER PRIMARY KEY,
        name TEXT,
        department TEXT,
        salary REAL
    )
");

// Inserir dados de exemplo
$connection->query("INSERT INTO users (name, city, salary) VALUES 
    ('Alice', 'São Paulo', 5000),
    ('Bob', 'Rio de Janeiro', 6000),
    ('Charlie', 'São Paulo', 7000)");

$connection->query("INSERT INTO employees (name, department, salary) VALUES 
    ('David', 'IT', 5500),
    ('Eve', 'HR', 4500),
    ('Frank', 'IT', 6500)");

echo "1. UNION - Combinar nomes de usuários e funcionários (sem duplicatas):\n";
$unionQuery = QueryBuilder::with($connection)
    ->select('name')
    ->from('users')
    ->union(
        QueryBuilder::with($connection)
            ->select('name')
            ->from('employees')
    )
    ->build();

echo "SQL: " . $unionQuery . "\n";
$unionResults = $connection->query($unionQuery)->fetchAll();
print_r($unionResults);

echo "\n2. UNION ALL - Combinar todos os nomes (com duplicatas):\n";
$unionAllQuery = QueryBuilder::with($connection)
    ->select('name')
    ->from('users')
    ->unionAll(
        QueryBuilder::with($connection)
            ->select('name')
            ->from('employees')
    )
    ->build();

echo "SQL: " . $unionAllQuery . "\n";
$unionAllResults = $connection->query($unionAllQuery)->fetchAll();
print_r($unionAllResults);

echo "\n3. WHERE EXISTS - Encontrar usuários que existem na tabela de funcionários:\n";
$existsQuery = QueryBuilder::with($connection)
    ->select('name', 'city')
    ->from('users')
    ->whereExists(
        QueryBuilder::with($connection)
            ->select('1')
            ->from('employees')
            ->where('employees.name', '=', 'users.name')
    )
    ->build();

echo "SQL: " . $existsQuery . "\n";
$existsResults = $connection->query($existsQuery)->fetchAll();
print_r($existsResults);

echo "\n4. WHERE NOT EXISTS - Encontrar usuários que NÃO existem na tabela de funcionários:\n";
$notExistsQuery = QueryBuilder::with($connection)
    ->select('name', 'city')
    ->from('users')
    ->whereNotExists(
        QueryBuilder::with($connection)
            ->select('1')
            ->from('employees')
            ->where('employees.name', '=', 'users.name')
    )
    ->build();

echo "SQL: " . $notExistsQuery . "\n";
$notExistsResults = $connection->query($notExistsQuery)->fetchAll();
print_r($notExistsResults);

echo "\n5. Combinação complexa - UNION com EXISTS:\n";
$complexQuery = QueryBuilder::with($connection)
    ->select('name', 'city', '"User" as type')
    ->from('users')
    ->whereExists(
        QueryBuilder::with($connection)
            ->select('1')
            ->from('employees')
            ->where('employees.salary', '>', 'users.salary')
    )
    ->union(
        QueryBuilder::with($connection)
            ->select('name', 'department as city', '"Employee" as type')
            ->from('employees')
            ->where('salary', '>', 5000)
    )
    ->build();

echo "SQL: " . $complexQuery . "\n";
$complexResults = $connection->query($complexQuery)->fetchAll();
print_r($complexResults);

echo "\n=== Demonstração concluída ===\n";

// Demonstração com Flat File (JSON)
echo "\n=== Demonstração com Flat Files (JSON) ===\n";

$jsonConnection = Connection::json()
    ->setDatabase(__DIR__ . '/data')
    ->connect();

// Criar dados JSON de exemplo
file_put_contents(__DIR__ . '/data/users.json', json_encode([
    ['id' => 1, 'name' => 'Alice', 'city' => 'São Paulo'],
    ['id' => 2, 'name' => 'Bob', 'city' => 'Rio de Janeiro'],
    ['id' => 3, 'name' => 'Charlie', 'city' => 'São Paulo']
]));

file_put_contents(__DIR__ . '/data/employees.json', json_encode([
    ['id' => 1, 'name' => 'David', 'department' => 'IT'],
    ['id' => 2, 'name' => 'Eve', 'department' => 'HR'],
    ['id' => 3, 'name' => 'Alice', 'department' => 'Finance']
]));

try {
    echo "UNION com JSON:\n";
    $jsonUnionQuery = QueryBuilder::with($jsonConnection)
        ->select('name')
        ->from('users.json')
        ->union(
            QueryBuilder::with($jsonConnection)
                ->select('name')
                ->from('employees.json')
        )
        ->build();

    echo "SQL (para referência): " . $jsonUnionQuery . "\n";
    $jsonUnionResults = QueryBuilder::with($jsonConnection)
        ->select('name')
        ->from('users.json')
        ->union(
            QueryBuilder::with($jsonConnection)
                ->select('name')
                ->from('employees.json')
        )
        ->fetchAll();
    print_r($jsonUnionResults);
} catch (Exception $e) {
    echo "Erro (esperado para algumas funcionalidades): " . $e->getMessage() . "\n";
}

echo "\n=== Finalizado ===\n";