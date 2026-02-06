# Comparação de Métodos Públicos - Connection vs Database Engines

## Classes Comparadas

- `src\Connection.php` (Facade/Strategy)
- `src\Engine\FirebirdConnection.php`
- `src\Engine\MySQLiConnection.php`
- `src\Engine\OCIConnection.php`
- `src\Engine\ODBCConnection.php`
- `src\Engine\PDOConnection.php`
- `src\Engine\PgSQLConnection.php`
- `src\Engine\SQLiteConnection.php`
- `src\Engine\SQLSrvConnection.php`

---

## Matriz de Métodos Públicos

| Método | Connection | Engines* |
|--------|:----------:|:--------:|
| `__construct` | ✓ | ✓ |
| `__call` | ✓ | ✓ |
| `__callStatic` | ✓ | ✓ |
| `connect` | ✓ | ✓ |
| `ping` | ✓ | ✓ |
| `disconnect` | ✓ | ✓ |
| `isConnected` | ✓ | ✓ |
| `getConnection` | ✓ | ✓ |
| `setConnection` | ✓ | ✓ |
| `beginTransaction` | ✓ | ✓ |
| `commit` | ✓ | ✓ |
| `rollback` | ✓ | ✓ |
| `inTransaction` | ✓ | ✓ |
| `lastInsertId` | ✓ | ✓ |
| `quote` | ✓ | ✓ |
| `getAllMetadata` | ✓ | ✓ |
| `getQueryString` | ✓ | ✓ |
| `setQueryString` | ✓ | ✓ |
| `getQueryParameters` | ✓ | ✓ |
| `setQueryParameters` | ✓ | ✓ |
| `getQueryRows` | ✓ | ✓ |
| `setQueryRows` | ✓ | ✓ |
| `getQueryColumns` | ✓ | ✓ |
| `setQueryColumns` | ✓ | ✓ |
| `getAffectedRows` | ✓ | ✓ |
| `setAffectedRows` | ✓ | ✓ |
| `getStatement` | ✓ | ✓ |
| `setStatement` | ✓ | ✓ |
| `query` | ✓ | ✓ |
| `prepare` | ✓ | ✓ |
| `exec` | ✓ | ✓ |
| `fetch` | ✓ | ✓ |
| `fetchAll` | ✓ | ✓ |
| `getAttribute` | ✓ | ✓ |
| `setAttribute` | ✓ | ✓ |
| `errorCode` | ✓ | ✓ |
| `errorInfo` | ✓ | ✓ |
| `setAllMetadata` | ✓ | ✓ |
| `bindParam` | ✓ | ✓ |
| `parse` | ✓ | ✓ |
| **`setStrategy`** | ✓ | - |
| **`getStrategy`** | ✓ | - |

*\* Engines = Firebird, MySQLi, OCI, ODBC, PDO, PgSQL, SQLite, SQLSrv (todas possuem os mesmos métodos)*

---

## Métodos Comuns (40 métodos)

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
| **`setStrategy`** | `public function setStrategy(IConnection $strategy): void` | Define a instância da estratégia (engine) |
| **`getStrategy`** | `public function getStrategy(): IConnection` | Obtém a instância da estratégia (engine) |

### **Database Engines** - Métodos exclusivos

*Nenhum método exclusivo. Todos os métodos das engines estão disponíveis na classe `Connection`.*

---

## Resumo Comparativo

| Classe | Total de Métodos | Métodos Exclusivos |
|--------|:----------------:|:------------------:|
| **Connection** | 42 | 2 (`setStrategy`, `getStrategy`) |
| FirebirdConnection | 40 | 0 |
| MySQLiConnection | 40 | 0 |
| OCIConnection | 40 | 0 |
| ODBCConnection | 40 | 0 |
| PDOConnection | 40 | 0 |
| PgSQLConnection | 40 | 0 |
| SQLiteConnection | 40 | 0 |
| SQLSrvConnection | 40 | 0 |

---

## Análise

### Connection.php (Facade/Strategy Pattern)
A classe `Connection` atua como um **facade** que implementa o **Strategy Pattern**, delegando as operações para a engine específica através dos métodos `setStrategy` e `getStrategy`.

### Database Engines
Todas as 8 classes de engine possuem uma API idêntica com 40 métodos públicos. Todos esses métodos estão agora disponíveis através da classe `Connection`.

### Compatibilidade
Os 40 métodos comuns garantem que a classe `Connection` pode ser usada de forma intercambiável com qualquer engine, mantendo a mesma interface pública completa para todas as operações de banco de dados. A `Connection` possui 2 métodos adicionais (`setStrategy` e `getStrategy`) para gerenciamento da estratégia.
