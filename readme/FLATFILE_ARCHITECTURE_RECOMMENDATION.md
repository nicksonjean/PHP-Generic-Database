# Recomendação Arquitetural: Flat File Engines

## Contexto

O projeto possui uma arquitetura baseada no **Strategy Pattern** onde a classe `Connection` atua como facade, permitindo trocar engines de banco de dados (MySQL, PostgreSQL, Oracle, etc.) de forma transparente. O objetivo é estender essa arquitetura para suportar **Flat File Engines** (JSON, CSV, XML, INI, YAML, NEON) com operações SQL-like baseadas no dialeto SQLite.

### Problema Identificado

A classe `JSONConnection` atual possui **62 métodos públicos**, sendo:
- **40 métodos**: Interface padrão (compatível com outras engines)
- **22 métodos**: Exclusivos para operações de flat file

Esses 22 métodos extras **quebram o contrato da interface** e impedem o uso transparente via Strategy Pattern.

---

## Análise dos 22 Métodos Exclusivos

### Categorização por Finalidade

| Categoria | Métodos | Uso Real |
|-----------|---------|----------|
| **Estrutura/Schema** | `mount`, `getSchema`, `getSchemaFile`, `getSchemaData`, `getStructure`, `setStructure`, `getTables`, `setTables`, `getTablePath` | Configuração interna |
| **I/O de Arquivo** | `load`, `save` | Operação interna |
| **Dados em Memória** | `getData`, `setData` | Estado interno |
| **Navegação** | `from`, `getCurrentTable` | Pode ser feito via SQL |
| **CRUD Direto** | `insert`, `update`, `delete`, `selectWhere` | Substituível por SQL |
| **Controle Fetch** | `getFetchedRows`, `setFetchedRows` | Estado interno |

### Conclusão da Análise

- **100% dos métodos exclusivos** são operações que podem ser:
  1. Internalizadas como métodos privados
  2. Executadas via SQL-like (`query`, `exec`, `prepare`)
  3. Configuradas via `setOptions`/`setAttribute`

---

## Opções Arquiteturais

### Opção 1: Remoção Total (Recomendada)

**Descrição**: Remover todos os 22 métodos públicos exclusivos, mantendo apenas a interface padrão de 40 métodos.

```
┌─────────────────────────────────────────────────────────────┐
│                      Connection                              │
│                   (Strategy Facade)                          │
├─────────────────────────────────────────────────────────────┤
│  setStrategy(IConnection)                                    │
│  getStrategy(): IConnection                                  │
│  [40 métodos da interface IConnection]                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
        ┌──────────────┴──────────────┐
        │                             │
        ▼                             ▼
┌───────────────────┐       ┌───────────────────┐
│ Database Engines  │       │ Flat File Engines │
├───────────────────┤       ├───────────────────┤
│ MySQLiConnection  │       │ JSONConnection    │
│ OCIConnection     │       │ CSVConnection     │
│ PgSQLConnection   │       │ XMLConnection     │
│ SQLiteConnection  │       │ INIConnection     │
│ FirebirdConnection│       │ YAMLConnection    │
│ PDOConnection     │       │ NEONConnection    │
│ ODBCConnection    │       │                   │
│ SQLSrvConnection  │       │                   │
└───────────────────┘       └───────────────────┘
        │                             │
        └──────────────┬──────────────┘
                       │
                       ▼
            ┌─────────────────────┐
            │    IConnection      │
            │   (40 métodos)      │
            └─────────────────────┘
```

**Prós**:
- Interface 100% consistente
- Intercambiabilidade total via Strategy
- QueryBuilder funciona sem modificações
- Menor superfície de API pública
- Princípio de Substituição de Liskov (LSP) respeitado

**Contras**:
- Perde acesso direto a operações de baixo nível (mitigável via handlers internos)

---

### Opção 2: Interface Segregada (Alternativa)

**Descrição**: Criar interface adicional `IFlatFileConnection` para métodos específicos.

```php
interface IFlatFileConnection extends IConnection
{
    public function load(?string $table = null): array;
    public function save(array $data, ?string $table = null): bool;
    public function getTables(): ?array;
    // ... outros métodos específicos
}
```

**Prós**:
- Mantém acesso a operações específicas quando necessário
- Type-safety para operações de flat file

**Contras**:
- Quebra a intercambiabilidade pura
- Requer type-checking no código cliente
- Viola o princípio ISP (Interface Segregation Principle) em alguns contextos

---

### Opção 3: Composição com Decorator (Alternativa)

**Descrição**: Classe wrapper que adiciona funcionalidades extras.

```php
$json = new JSONConnection();
$flatFile = new FlatFileDecorator($json);
$flatFile->load('users'); // Método específico

// Uso via Connection (sem decorator)
Connection::setStrategy($json);
Connection::query('SELECT * FROM users');
```

**Prós**:
- Separação clara de responsabilidades
- Não polui a interface principal

**Contras**:
- Complexidade adicional
- Dois objetos para gerenciar

---

## Recomendação: Opção 1 - Remoção Total

### Justificativa

1. **Consistência Arquitetural**: O Strategy Pattern funciona melhor quando todas as estratégias têm a mesma interface.

2. **SQL-Like é Suficiente**: Todas as operações CRUD podem ser feitas via SQL:
   ```php
   // Ao invés de:
   $json->from('users')->insert(['name' => 'John']);

   // Usar:
   Connection::exec("INSERT INTO users (name) VALUES ('John')");
   ```

3. **Configuração via Options/Attributes**: Schema e estrutura podem ser configurados na conexão:
   ```php
   JSONConnection::setDatabase('./data')
       ->setOptions([JSON::ATTR_AUTO_SAVE => true])
       ->connect();
   ```

4. **Handlers Internos Preservados**: A lógica interna (FetchHandler, StatementsHandler, StructureHandler) continua funcionando, apenas não é exposta publicamente.

---

## Plano de Implementação

### Fase 1: Refatoração da JSONConnection

#### 1.1 Métodos a Tornar Privados

```php
// DE: public function mount(): array|Structure|Exceptions
// PARA: private function mount(): array|Structure|Exceptions

// DE: public function getTablePath(string $table): string
// PARA: private function getTablePath(string $table): string

// DE: public function getSchema(): ?Structure
// PARA: private function getSchema(): ?Structure

// DE: public function getSchemaFile(): ?string
// PARA: private function getSchemaFile(): ?string

// DE: public function getSchemaData(): ?array
// PARA: private function getSchemaData(): ?array

// DE: public function getTables(): ?array
// PARA: private function getTables(): ?array

// DE: public function setTables(array $tables): void
// PARA: private function setTables(array $tables): void

// DE: public function getStructure(): ?Structure
// PARA: private function getStructure(): ?Structure

// DE: public function setStructure(array|Structure|Exceptions $structure): void
// PARA: private function setStructure(array|Structure|Exceptions $structure): void

// DE: public function load(?string $table = null): array
// PARA: private function load(?string $table = null): array

// DE: public function save(array $data, ?string $table = null): bool
// PARA: private function save(array $data, ?string $table = null): bool

// DE: public function getData(): array
// PARA: private function getData(): array

// DE: public function setData(array $data): void
// PARA: private function setData(array $data): void

// DE: public function from(string $table): JSONConnection
// PARA: private function from(string $table): JSONConnection

// DE: public function getCurrentTable(): ?string
// PARA: private function getCurrentTable(): ?string

// DE: public function insert(array $row): bool
// PARA: private function insert(array $row): bool

// DE: public function update(array $data, array $where): int
// PARA: private function update(array $data, array $where): int

// DE: public function delete(array $where): int
// PARA: private function delete(array $where): int

// DE: public function selectWhere(array $columns, array $where): array
// PARA: private function selectWhere(array $columns, array $where): array
```

#### 1.2 Métodos a Mover para StatementsHandler

```php
// getFetchedRows() e setFetchedRows() já estão delegados ao StatementsHandler
// Remover da interface pública
```

#### 1.3 Estrutura Proposta

```php
#[AllowDynamicProperties]
class JSONConnection implements IConnection
{
    use Methods;
    use Singleton;

    // Handlers (privados)
    private static IFlatFileFetch $fetchHandler;
    private static IFlatFileStatements $statementsHandler;
    private static IDSN $dsnHandler;
    private static IAttributes $attributesHandler;
    private static IOptions $optionsHandler;
    private static IArguments $argumentsHandler;
    private static ITransactions $transactionsHandler;
    private static StructureHandler $structureHandler;

    // ═══════════════════════════════════════════════════════════
    // MÉTODOS PÚBLICOS (40 - Interface IConnection)
    // ═══════════════════════════════════════════════════════════

    public function __construct() { ... }
    public function __call(string $name, array $arguments) { ... }
    public static function __callStatic(string $name, array $arguments) { ... }
    public function connect(): JSONConnection { ... }
    public function ping(): bool { ... }
    public function disconnect(): void { ... }
    public function isConnected(): bool { ... }
    public function getConnection(): mixed { ... }
    public function setConnection(mixed $connection): mixed { ... }
    public function beginTransaction(): bool { ... }
    public function commit(): bool { ... }
    public function rollback(): bool { ... }
    public function inTransaction(): bool { ... }
    public function lastInsertId(?string $name = null): string|int|false { ... }
    public function quote(mixed ...$params): string|int { ... }
    public function getAllMetadata(): object { ... }
    public function setAllMetadata(): void { ... }
    public function getQueryString(): string { ... }
    public function setQueryString(string $params): void { ... }
    public function getQueryParameters(): ?array { ... }
    public function setQueryParameters(?array $params): void { ... }
    public function getQueryRows(): int|false { ... }
    public function setQueryRows(callable|int|false $params): void { ... }
    public function getQueryColumns(): int|false { ... }
    public function setQueryColumns(int|false $params): void { ... }
    public function getAffectedRows(): int|false { ... }
    public function setAffectedRows(int|false $params): void { ... }
    public function getStatement(): mixed { ... }
    public function setStatement(mixed $statement): void { ... }
    public function bindParam(object $params): void { ... }
    public function parse(mixed ...$params): string { ... }
    public function query(mixed ...$params): static|null { ... }
    public function prepare(mixed ...$params): static|null { ... }
    public function exec(mixed ...$params): mixed { ... }
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed { ... }
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool { ... }
    public function getAttribute(mixed $name): mixed { ... }
    public function setAttribute(mixed $name, mixed $value): void { ... }
    public function errorCode(mixed $inst = null): int|string|bool { ... }
    public function errorInfo(mixed $inst = null): string|bool|array { ... }

    // ═══════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS (Operações internas de flat file)
    // ═══════════════════════════════════════════════════════════

    private function preConnect(): JSONConnection { ... }
    private function postConnect(): JSONConnection { ... }
    private function realConnect(string $database): JSONConnection { ... }
    private function parseDsn(): string|Exceptions { ... }
    private function isDmlQuery(string $query): bool { ... }

    // Handlers getters (privados)
    private function getFetchHandler(): IFlatFileFetch { ... }
    private function getStatementsHandler(): IFlatFileStatements { ... }
    private function getDsnHandler(): IDSN { ... }
    private function getAttributesHandler(): IAttributes { ... }
    private function getOptionsHandler(): IOptions { ... }
    private function getArgumentsHandler(): IArguments { ... }
    private function getTransactionsHandler(): ITransactions { ... }
    private function getStructureHandler(): StructureHandler { ... }

    // Operações de estrutura (privadas)
    private function mount(): array|Structure|Exceptions { ... }
    private function getTablePath(string $table): string { ... }
    private function getSchema(): ?Structure { ... }
    private function getSchemaFile(): ?string { ... }
    private function getSchemaData(): ?array { ... }
    private function getTables(): ?array { ... }
    private function setTables(array $tables): void { ... }
    private function getStructure(): ?Structure { ... }
    private function setStructure(array|Structure|Exceptions $structure): void { ... }

    // Operações de I/O (privadas)
    private function load(?string $table = null): array { ... }
    private function save(array $data, ?string $table = null): bool { ... }

    // Operações de dados (privadas)
    private function getData(): array { ... }
    private function setData(array $data): void { ... }
    private function from(string $table): JSONConnection { ... }
    private function getCurrentTable(): ?string { ... }

    // Operações CRUD diretas (privadas - usadas internamente pelo SQL parser)
    private function insert(array $row): bool { ... }
    private function update(array $data, array $where): int { ... }
    private function delete(array $where): int { ... }
    private function selectWhere(array $columns, array $where): array { ... }
}
```

---

### Fase 2: Criação das Outras Flat File Engines

#### 2.1 Estrutura de Diretórios Proposta

```
src/Engine/
├── FlatFiles/                    # Código compartilhado
│   ├── Interfaces/
│   │   ├── IFlatFileFetch.php
│   │   ├── IFlatFileStatements.php
│   │   └── IFlatFileParser.php
│   ├── Parsers/
│   │   ├── SQLParser.php         # Parser SQL-like (dialeto SQLite)
│   │   ├── SelectParser.php
│   │   ├── InsertParser.php
│   │   ├── UpdateParser.php
│   │   └── DeleteParser.php
│   ├── DataProcessor.php
│   └── TransactionManager.php
│
├── JSON/
│   ├── Connection/
│   │   ├── JSON.php              # Constantes e atributos
│   │   ├── DSN/
│   │   ├── Fetch/
│   │   ├── Statements/
│   │   ├── Transactions/
│   │   └── Structure/
│   └── JSONConnection.php
│
├── CSV/
│   ├── Connection/
│   │   ├── CSV.php
│   │   ├── DSN/
│   │   ├── Fetch/
│   │   ├── Statements/
│   │   ├── Transactions/
│   │   └── Structure/
│   └── CSVConnection.php
│
├── XML/
│   ├── Connection/
│   │   ├── XML.php
│   │   ├── DSN/
│   │   ├── Fetch/
│   │   ├── Statements/
│   │   ├── Transactions/
│   │   └── Structure/
│   └── XMLConnection.php
│
├── INI/
│   ├── Connection/
│   │   └── ...
│   └── INIConnection.php
│
├── YAML/
│   ├── Connection/
│   │   └── ...
│   └── YAMLConnection.php
│
└── NEON/
    ├── Connection/
    │   └── ...
    └── NEONConnection.php
```

#### 2.2 Classe Base Abstrata (Opcional)

```php
abstract class AbstractFlatFileConnection implements IConnection
{
    use Methods;
    use Singleton;

    protected static mixed $connection;
    protected static IFlatFileFetch $fetchHandler;
    protected static IFlatFileStatements $statementsHandler;
    protected static StructureHandler $structureHandler;
    // ... outros handlers

    // Métodos comuns implementados
    public function beginTransaction(): bool { ... }
    public function commit(): bool { ... }
    public function rollback(): bool { ... }
    public function inTransaction(): bool { ... }

    // Métodos abstratos para cada engine implementar
    abstract protected function loadFile(string $path): array;
    abstract protected function saveFile(string $path, array $data): bool;
    abstract protected function parseFile(string $content): array;
    abstract protected function formatFile(array $data): string;
}
```

---

### Fase 3: SQL Parser (Dialeto SQLite)

#### 3.1 Operações Suportadas

| Categoria | Operações | Exemplo |
|-----------|-----------|---------|
| **DQL** | SELECT | `SELECT * FROM users WHERE age > 18` |
| **DML** | INSERT | `INSERT INTO users (name, age) VALUES ('John', 25)` |
| **DML** | UPDATE | `UPDATE users SET age = 26 WHERE name = 'John'` |
| **DML** | DELETE | `DELETE FROM users WHERE id = 1` |
| **TCL** | BEGIN | `BEGIN TRANSACTION` |
| **TCL** | COMMIT | `COMMIT` |
| **TCL** | ROLLBACK | `ROLLBACK` |

#### 3.2 Fluxo de Execução

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   SQL Query     │────▶│   SQL Parser    │────▶│  Parsed Query   │
│                 │     │ (SQLite dialect)│     │    (AST)        │
└─────────────────┘     └─────────────────┘     └────────┬────────┘
                                                         │
                                                         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    Result       │◀────│ Data Processor  │◀────│  Query Executor │
│                 │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

---

### Fase 4: Integração com Connection e QueryBuilder

#### 4.1 Uso via Connection (Strategy)

```php
// Configuração
$json = new JSONConnection();
$json->setDatabase('./data')
     ->setCharset('UTF-8')
     ->setOptions([
         JSON::ATTR_AUTO_SAVE => true,
         JSON::ATTR_PRETTY_PRINT => true
     ])
     ->connect();

// Definir como estratégia
Connection::setStrategy($json);

// Uso transparente (idêntico a MySQL, PostgreSQL, etc.)
Connection::query("SELECT * FROM users WHERE status = 'active'");
$users = Connection::fetchAll();

Connection::exec("INSERT INTO users (name, email) VALUES ('John', 'john@email.com')");
$lastId = Connection::lastInsertId();

Connection::beginTransaction();
Connection::exec("UPDATE users SET status = 'inactive' WHERE last_login < '2024-01-01'");
Connection::commit();
```

#### 4.2 Uso via QueryBuilder

```php
// QueryBuilder funciona sem modificações
$qb = new QueryBuilder(Connection::getInstance());

$users = $qb->select('*')
            ->from('users')
            ->where('age', '>', 18)
            ->orderBy('name')
            ->get();

$qb->insert('users')
   ->values(['name' => 'Jane', 'age' => 30])
   ->execute();

$qb->update('users')
   ->set(['status' => 'active'])
   ->where('id', '=', 5)
   ->execute();
```

#### 4.3 Troca de Engine Transparente

```php
// Desenvolvimento local com JSON
$json = new JSONConnection();
$json->setDatabase('./data')->connect();
Connection::setStrategy($json);

// Produção com MySQL (mesma API)
$mysql = new MySQLiConnection();
$mysql->setHost('localhost')
      ->setDatabase('mydb')
      ->setUser('root')
      ->setPassword('secret')
      ->connect();
Connection::setStrategy($mysql);

// O código cliente não muda
Connection::query("SELECT * FROM users");
$users = Connection::fetchAll();
```

---

## Cronograma Sugerido

| Fase | Tarefa | Prioridade |
|------|--------|------------|
| 1.1 | Refatorar JSONConnection (métodos privados) | Alta |
| 1.2 | Atualizar testes da JSONConnection | Alta |
| 2.1 | Implementar CSVConnection | Alta |
| 2.2 | Implementar XMLConnection | Média |
| 2.3 | Implementar YAMLConnection | Média |
| 2.4 | Implementar INIConnection | Baixa |
| 2.5 | Implementar NEONConnection | Baixa |
| 3.1 | Refinar SQL Parser (SELECT avançado) | Média |
| 3.2 | Adicionar suporte a JOINs (opcional) | Baixa |
| 4.1 | Testes de integração com Connection | Alta |
| 4.2 | Testes de integração com QueryBuilder | Alta |
| 4.3 | Documentação | Média |

---

## Especificidades por Formato de Arquivo

| Formato | Estrutura | Considerações |
|---------|-----------|---------------|
| **JSON** | Array de objetos | Suporta tipos nativos, aninhamento |
| **CSV** | Tabular | Requer schema para tipos, delimitadores configuráveis |
| **XML** | Hierárquico | XPath para queries, atributos vs elementos |
| **YAML** | Hierárquico | Suporta referências, tipos nativos |
| **INI** | Seções/Chaves | Estrutura plana, seções como tabelas |
| **NEON** | Similar YAML | Específico para PHP/Nette |

### Schema por Formato

```
data/
├── schema.json          # Para JSON
├── schema.ini           # Para CSV (padrão Microsoft)
├── schema.xsd           # Para XML (opcional)
├── schema.yaml          # Para YAML
└── schema.neon          # Para NEON
```

---

## Conclusão

### Recomendação Final

**Adotar a Opção 1 (Remoção Total dos métodos públicos exclusivos)** pelos seguintes motivos:

1. **Consistência**: Interface idêntica em todas as engines
2. **Intercambiabilidade**: Troca de engine sem alteração de código
3. **LSP**: Respeito ao Princípio de Substituição de Liskov
4. **Simplicidade**: API única e documentação simplificada
5. **QueryBuilder**: Funciona sem modificações

### Benefícios Esperados

- Código cliente agnóstico à engine
- Facilidade de testes (mock de qualquer engine)
- Migração simplificada entre formatos
- Manutenção centralizada da interface

### Riscos Mitigados

- Perda de funcionalidades: Métodos continuam existindo (privados)
- Acesso de baixo nível: Disponível via handlers internos se necessário
- Performance: Otimizações podem ser feitas nos handlers sem afetar a interface

---

## Apêndice: Checklist de Implementação

### JSONConnection (Refatoração) ✅

- [x] Tornar 22 métodos públicos em privados
- [x] Remover interfaces extras (IStructure, IFetch, IStatements, etc.) da declaração da classe
- [x] Manter apenas `implements IConnection`
- [ ] Atualizar testes unitários (não há testes específicos)
- [x] Validar integração com Connection
- [ ] Validar integração com QueryBuilder

### CSVConnection (Refatoração) ✅

- [x] Tornar métodos exclusivos em privados
- [x] Remover interfaces extras (IFlatFileConnection, IFetch, IStatements) da declaração da classe
- [x] Manter apenas `implements IConnection`

### XMLConnection (Refatoração) ✅

- [x] Tornar métodos exclusivos em privados
- [x] Remover interfaces extras (IFlatFileConnection, IFetch, IStatements) da declaração da classe
- [x] Manter apenas `implements IConnection`

### YAMLConnection (Refatoração) ✅

- [x] Tornar métodos exclusivos em privados
- [x] Remover interfaces extras (IFlatFileConnection, IFetch, IStatements) da declaração da classe
- [x] Manter apenas `implements IConnection`

### Nova Engine (Template)

- [ ] Criar estrutura de diretórios
- [ ] Implementar classe de constantes (Ex: `INI.php`, `NEON.php`)
- [ ] Implementar handlers (DSN, Fetch, Statements, etc.)
- [ ] Implementar classe principal (Ex: `INIConnection.php`, `NEONConnection.php`)
- [ ] Implementar parser de arquivo específico
- [ ] Criar testes unitários
- [ ] Criar testes de integração
- [ ] Documentar configurações específicas
