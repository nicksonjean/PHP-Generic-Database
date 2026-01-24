# Comparação de Métodos Públicos - Database Connections

## Classes Comparadas

1. `src\Engine\FirebirdConnection.php`
2. `src\Engine\MySQLiConnection.php`
3. `src\Engine\OCIConnection.php`
4. `src\Engine\ODBCConnection.php`
5. `src\Engine\PDOConnection.php`
6. `src\Engine\PgSQLConnection.php`
7. `src\Engine\SQLiteConnection.php`
8. `src\Engine\SQLSrvConnection.php`

---

## Matriz de Métodos Públicos

| Método | Firebird | MySQLi | OCI | ODBC | PDO | PgSQL | SQLite | SQLSrv |
|--------|:--------:|:------:|:---:|:----:|:---:|:-----:|:------:|:------:|
| `__construct` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `__call` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `__callStatic` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `connect` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `ping` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `disconnect` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `isConnected` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getConnection` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setConnection` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `beginTransaction` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `commit` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `rollback` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `inTransaction` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `lastInsertId` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `quote` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setAllMetadata` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getAllMetadata` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getQueryString` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setQueryString` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getQueryParameters` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setQueryParameters` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getQueryRows` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setQueryRows` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getQueryColumns` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setQueryColumns` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getAffectedRows` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setAffectedRows` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getStatement` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setStatement` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `bindParam` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `parse` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `query` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `prepare` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `exec` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `fetch` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `fetchAll` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `getAttribute` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `setAttribute` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `errorCode` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `errorInfo` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

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
| `setAllMetadata` | Reseta os metadados da query |
| `getAllMetadata` | Obtém todos os metadados |
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
| `bindParam` | Vincula um parâmetro |
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

## Métodos Exclusivos por Classe

**Nenhuma classe possui métodos públicos exclusivos.**

Todas as 8 classes de conexão possuem exatamente os mesmos 40 métodos públicos, garantindo uma interface consistente e uniforme entre todos os engines de banco de dados.

---

## Detalhamento por Classe

### Lista de Métodos Públicos (comum a todas as classes)

```
__construct, __call, __callStatic, connect, ping, disconnect, isConnected,
getConnection, setConnection, beginTransaction, commit, rollback,
inTransaction, lastInsertId, quote, setAllMetadata, getAllMetadata,
getQueryString, setQueryString, getQueryParameters, setQueryParameters,
getQueryRows, setQueryRows, getQueryColumns, setQueryColumns, getAffectedRows,
setAffectedRows, getStatement, setStatement, bindParam, parse, query, prepare,
exec, fetch, fetchAll, getAttribute, setAttribute, errorCode, errorInfo
```

### Classes

- **FirebirdConnection.php** - 40 métodos públicos
- **MySQLiConnection.php** - 40 métodos públicos
- **OCIConnection.php** - 40 métodos públicos
- **ODBCConnection.php** - 40 métodos públicos
- **PDOConnection.php** - 40 métodos públicos
- **PgSQLConnection.php** - 40 métodos públicos
- **SQLiteConnection.php** - 40 métodos públicos
- **SQLSrvConnection.php** - 40 métodos públicos

---

## Resumo

| Classe | Total de Métodos | Métodos Exclusivos |
|--------|:----------------:|:------------------:|
| FirebirdConnection | 40 | 0 |
| MySQLiConnection | 40 | 0 |
| OCIConnection | 40 | 0 |
| ODBCConnection | 40 | 0 |
| PDOConnection | 40 | 0 |
| PgSQLConnection | 40 | 0 |
| SQLiteConnection | 40 | 0 |
| SQLSrvConnection | 40 | 0 |

**Todas as 8 classes de conexão possuem exatamente os mesmos 40 métodos públicos, proporcionando uma API consistente e intercambiável entre diferentes engines de banco de dados.**
