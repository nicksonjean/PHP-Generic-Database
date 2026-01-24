# Comparação de Métodos Públicos - JSONConnection vs Database Engines Tradicionais

## Classes Comparadas

### Engines Tradicionais (do documento CONNECTION_VS_ENGINES_COMPARISON.md)
- `src\Connection.php` (Facade/Strategy)
- `src\Engine\FirebirdConnection.php`
- `src\Engine\MySQLiConnection.php`
- `src\Engine\OCIConnection.php`
- `src\Engine\ODBCConnection.php`
- `src\Engine\PDOConnection.php`
- `src\Engine\PgSQLConnection.php`
- `src\Engine\SQLiteConnection.php`
- `src\Engine\SQLSrvConnection.php`

### Flat File Engine
- `src\Engine\JSONConnection.php`

---

## Matriz de Métodos Públicos

| Método | Connection | Engines Tradicionais* | JSONConnection |
|--------|:----------:|:---------------------:|:--------------:|
| `__construct` | ✓ | ✓ | ✓ |
| `__call` | ✓ | ✓ | ✓ |
| `__callStatic` | ✓ | ✓ | ✓ |
| `connect` | ✓ | ✓ | ✓ |
| `ping` | ✓ | ✓ | ✓ |
| `disconnect` | ✓ | ✓ | ✓ |
| `isConnected` | ✓ | ✓ | ✓ |
| `getConnection` | ✓ | ✓ | ✓ |
| `setConnection` | ✓ | ✓ | ✓ |
| `beginTransaction` | ✓ | ✓ | ✓ |
| `commit` | ✓ | ✓ | ✓ |
| `rollback` | ✓ | ✓ | ✓ |
| `inTransaction` | ✓ | ✓ | ✓ |
| `lastInsertId` | ✓ | ✓ | ✓ |
| `quote` | ✓ | ✓ | ✓ |
| `getAllMetadata` | ✓ | ✓ | ✓ |
| `setAllMetadata` | ✓ | ✓ | ✓ |
| `getQueryString` | ✓ | ✓ | ✓ |
| `setQueryString` | ✓ | ✓ | ✓ |
| `getQueryParameters` | ✓ | ✓ | ✓ |
| `setQueryParameters` | ✓ | ✓ | ✓ |
| `getQueryRows` | ✓ | ✓ | ✓ |
| `setQueryRows` | ✓ | ✓ | ✓ |
| `getQueryColumns` | ✓ | ✓ | ✓ |
| `setQueryColumns` | ✓ | ✓ | ✓ |
| `getAffectedRows` | ✓ | ✓ | ✓ |
| `setAffectedRows` | ✓ | ✓ | ✓ |
| `getStatement` | ✓ | ✓ | ✓ |
| `setStatement` | ✓ | ✓ | ✓ |
| `query` | ✓ | ✓ | ✓ |
| `prepare` | ✓ | ✓ | ✓ |
| `exec` | ✓ | ✓ | ✓ |
| `fetch` | ✓ | ✓ | ✓ |
| `fetchAll` | ✓ | ✓ | ✓ |
| `getAttribute` | ✓ | ✓ | ✓ |
| `setAttribute` | ✓ | ✓ | ✓ |
| `errorCode` | ✓ | ✓ | ✓ |
| `errorInfo` | ✓ | ✓ | ✓ |
| `bindParam` | ✓ | ✓ | ✓ |
| `parse` | ✓ | ✓ | ✓ |
| **`setStrategy`** | ✓ | - | - |
| **`getStrategy`** | ✓ | - | - |
| **`mount`** | - | - | ✓ |
| **`getTablePath`** | - | - | ✓ |
| **`getSchema`** | - | - | ✓ |
| **`getSchemaFile`** | - | - | ✓ |
| **`getSchemaData`** | - | - | ✓ |
| **`getTables`** | - | - | ✓ |
| **`setTables`** | - | - | ✓ |
| **`getStructure`** | - | - | ✓ |
| **`setStructure`** | - | - | ✓ |
| **`load`** | - | - | ✓ |
| **`save`** | - | - | ✓ |
| **`getData`** | - | - | ✓ |
| **`setData`** | - | - | ✓ |
| **`from`** | - | - | ✓ |
| **`getCurrentTable`** | - | - | ✓ |
| **`insert`** | - | - | ✓ |
| **`update`** | - | - | ✓ |
| **`delete`** | - | - | ✓ |
| **`selectWhere`** | - | - | ✓ |
| **`getFetchedRows`** | - | - | ✓ |
| **`setFetchedRows`** | - | - | ✓ |

*\* Engines Tradicionais = Firebird, MySQLi, OCI, ODBC, PDO, PgSQL, SQLite, SQLSrv (todas possuem os mesmos métodos)*

---

## Métodos Comuns a Todas as Classes (40 métodos)

| Método | Descrição |
|--------|-----------|
| `__construct` | Construtor da classe |
| `__call` | Intercepta chamadas de métodos inacessíveis em contexto de objeto |
| `__callStatic` | Intercepta chamadas de métodos inacessíveis em contexto estático |
| `connect` | Estabelece conexão com o banco de dados |
| `ping` | Verifica se a conexão está ativa |
| `disconnect` | Desconecta do banco de dados |
| `isConnected` | Retorna se está conectado |
| `getConnection` | Obtém a instância da conexão |
| `setConnection` | Define a instância da conexão |
| `beginTransaction` | Inicia uma transação |
| `commit` | Confirma a transação |
| `rollback` | Reverte a transação |
| `inTransaction` | Verifica se está em transação |
| `lastInsertId` | Retorna o último ID inserido |
| `quote` | Escapa uma string para uso em SQL |
| `getAllMetadata` | Obtém todos os metadados |
| `setAllMetadata` | Reseta os metadados da query |
| `getQueryString` | Obtém a string da query |
| `setQueryString` | Define a string da query |
| `getQueryParameters` | Obtém os parâmetros da query |
| `setQueryParameters` | Define os parâmetros da query |
| `getQueryRows` | Obtém o número de linhas da query |
| `setQueryRows` | Define o número de linhas da query |
| `getQueryColumns` | Obtém o número de colunas |
| `setQueryColumns` | Define o número de colunas |
| `getAffectedRows` | Obtém o número de linhas afetadas |
| `setAffectedRows` | Define o número de linhas afetadas |
| `getStatement` | Obtém o statement |
| `setStatement` | Define o statement |
| `bindParam` | Vincula um parâmetro à query |
| `parse` | Analisa uma instrução SQL |
| `query` | Executa uma query SQL |
| `prepare` | Prepara uma query |
| `exec` | Executa uma instrução SQL |
| `fetch` | Busca a próxima linha |
| `fetchAll` | Busca todas as linhas |
| `getAttribute` | Obtém um atributo |
| `setAttribute` | Define um atributo |
| `errorCode` | Obtém o código de erro |
| `errorInfo` | Obtém informações do erro |

---

## Métodos Exclusivos

### **Connection.php** - Métodos exclusivos (2 métodos)

| Método | Assinatura | Descrição |
|--------|-----------|-----------|
| `setStrategy` | `public function setStrategy(IConnection $strategy): void` | Define a instância da estratégia (engine) |
| `getStrategy` | `public function getStrategy(): IConnection` | Obtém a instância da estratégia (engine) |

### **Engines Tradicionais** - Métodos exclusivos

*Nenhum método exclusivo. Todos os métodos das engines estão disponíveis na classe `Connection`.*

### **JSONConnection** - Métodos exclusivos (22 métodos)

| Método | Assinatura | Descrição |
|--------|-----------|-----------|
| `mount` | `public function mount(): array\|Structure\|Exceptions` | Monta e retorna a estrutura do banco de dados |
| `getTablePath` | `public function getTablePath(string $table): string` | Obtém o caminho completo do arquivo da tabela |
| `getSchema` | `public function getSchema(): ?Structure` | Obtém o schema do banco de dados |
| `getSchemaFile` | `public function getSchemaFile(): ?string` | Obtém o arquivo do schema |
| `getSchemaData` | `public function getSchemaData(): ?array` | Obtém os dados do schema |
| `getTables` | `public function getTables(): ?array` | Obtém a lista de tabelas disponíveis |
| `setTables` | `public function setTables(array $tables): void` | Define a lista de tabelas |
| `getStructure` | `public function getStructure(): ?Structure` | Obtém a estrutura do banco de dados |
| `setStructure` | `public function setStructure(array\|Structure\|Exceptions $structure): void` | Define a estrutura do banco de dados |
| `load` | `public function load(?string $table = null): array` | Carrega dados de um arquivo JSON de tabela |
| `save` | `public function save(array $data, ?string $table = null): bool` | Salva dados em um arquivo JSON de tabela |
| `getData` | `public function getData(): array` | Obtém os dados atuais em memória |
| `setData` | `public function setData(array $data): void` | Define os dados em memória |
| `from` | `public function from(string $table): JSONConnection` | Define a tabela ativa atual (fluent interface) |
| `getCurrentTable` | `public function getCurrentTable(): ?string` | Obtém a tabela ativa atual |
| `insert` | `public function insert(array $row): bool` | Insere uma nova linha na tabela |
| `update` | `public function update(array $data, array $where): int` | Atualiza linhas que correspondem aos critérios |
| `delete` | `public function delete(array $where): int` | Deleta linhas que correspondem aos critérios |
| `selectWhere` | `public function selectWhere(array $columns, array $where): array` | Seleciona linhas que correspondem aos critérios |
| `getFetchedRows` | `public function getFetchedRows(): int` | Obtém o número de linhas buscadas |
| `setFetchedRows` | `public function setFetchedRows(int $params): void` | Define o número de linhas buscadas |

---

## Resumo Comparativo

| Classe | Total de Métodos | Métodos Comuns | Métodos Exclusivos |
|--------|:----------------:|:--------------:|:------------------:|
| **Connection** | 42 | 40 | 2 (`setStrategy`, `getStrategy`) |
| FirebirdConnection | 40 | 40 | 0 |
| MySQLiConnection | 40 | 40 | 0 |
| OCIConnection | 40 | 40 | 0 |
| ODBCConnection | 40 | 40 | 0 |
| PDOConnection | 40 | 40 | 0 |
| PgSQLConnection | 40 | 40 | 0 |
| SQLiteConnection | 40 | 40 | 0 |
| SQLSrvConnection | 40 | 40 | 0 |
| **JSONConnection** | 62 | 40 | 22 |

---

## Análise

### Connection.php (Facade/Strategy Pattern)
A classe `Connection` atua como um **facade** que implementa o **Strategy Pattern**, delegando as operações para a engine específica através dos métodos `setStrategy` e `getStrategy`.

### Database Engines Tradicionais
Todas as 8 classes de engine possuem uma API idêntica com 40 métodos públicos. Todos esses métodos estão disponíveis através da classe `Connection`.

### JSONConnection (Flat File Engine)
A classe `JSONConnection` é uma implementação especializada para bancos de dados baseados em arquivos JSON. Além dos 40 métodos padrão da interface, ela oferece **22 métodos exclusivos** para:

1. **Gerenciamento de Estrutura/Schema** (9 métodos):
   - `mount`, `getTablePath`, `getSchema`, `getSchemaFile`, `getSchemaData`
   - `getTables`, `setTables`, `getStructure`, `setStructure`

2. **Operações de Arquivo** (2 métodos):
   - `load`, `save`

3. **Manipulação de Dados em Memória** (2 métodos):
   - `getData`, `setData`

4. **Navegação de Tabela** (2 métodos):
   - `from`, `getCurrentTable`

5. **Operações CRUD Diretas** (4 métodos):
   - `insert`, `update`, `delete`, `selectWhere`

6. **Controle de Fetch** (2 métodos):
   - `getFetchedRows`, `setFetchedRows`

### Compatibilidade
- Os 40 métodos comuns garantem que `JSONConnection` pode ser usada de forma intercambiável com qualquer engine tradicional através da classe `Connection`
- Os 22 métodos exclusivos fornecem funcionalidades específicas para operações com arquivos flat file que não fazem sentido em bancos de dados relacionais tradicionais
- A `JSONConnection` implementa as interfaces `IConnection`, `IFetch`, `IStatements`, `IDSN`, `IArguments`, `ITransactions` e `IStructure`

### Diferenças Arquiteturais

| Aspecto | Engines Tradicionais | JSONConnection |
|---------|---------------------|----------------|
| Armazenamento | Servidor de banco de dados | Arquivos JSON |
| Conexão | TCP/Socket | Sistema de arquivos |
| Transações | Nativas do SGBD | Simuladas em memória |
| Schema | Definido no banco | Arquivo Schema.json |
| Operações CRUD | Via SQL | Métodos diretos + SQL parseado |
| Memória | Streaming | Carrega em memória |

---

## Interfaces Implementadas

### Engines Tradicionais
- `IConnection`

### JSONConnection
- `IConnection`
- `IFetch`
- `IStatements`
- `IDSN`
- `IArguments`
- `ITransactions`
- `IStructure`

---

## Conclusão

A `JSONConnection` mantém **100% de compatibilidade** com a interface padrão das engines tradicionais (40 métodos comuns), permitindo seu uso transparente através do padrão Strategy implementado na classe `Connection`. Os 22 métodos exclusivos são extensões que permitem operações específicas para flat files, como manipulação direta de arquivos e operações CRUD sem necessidade de SQL.
