# Comparação Unificada: Connection e QueryBuilder (Facade/Strategy e Engines)

> **Documento unificado.** Esta análise substitui e consolida os métodos Connection e QueryBuilder Genéricos e Especializados para Database e Flat Files.
> Para atualizações futuras, edite este arquivo e mantenha a estrutura abaixo.

**Última atualização:** 2025-02-06
**Escopo:** Todas as classes de Conexão e QueryBuilder (Facade/Strategy + Engines tradicionais + Flat Files).


**clearFetchCache (PDO/ODBC):** Para evitar loop infinito, **não** se chama `clearFetchCache` no início de `query()`/`prepare()`/`exec()`. Em vez de `method_exists`, passou a existir a interface opcional **IFetchCache** (`Interfaces/Connection/IFetchCache.php`) com o método `clearFetchCache(): void`. Apenas PDOConnection e ODBCConnection implementam essa interface; o StatementsHandler de cada engine chama `clearFetchCache()` quando a conexão é `instanceof IFetchCache` (dentro de `prepareStatement()`), mantendo o ponto de chamada original e sem reflexão.

---

## 1. Convenções e como atualizar

- **Métodos comuns:** presentes em todas as classes do mesmo tipo (Connection ou QueryBuilder), incluindo Facade e todas as engines.
- **Métodos exclusivos:** presentes apenas na Facade/Strategy (ex.: `setStrategy`, `getStrategy`). PDO e ODBC implementam ainda a interface opcional **IFetchCache** (`clearFetchCache`), chamada pelo StatementsHandler via `instanceof`. Nenhuma Connection Flat possui métodos exclusivos.
- **Interfaces:** `IConnection` e `IQueryBuilder` definem o contrato mínimo; `IConnectionStrategy` e `IQueryBuilderStrategy` acrescentam `setStrategy`/`getStrategy`.
- Ao adicionar nova engine ou método, atualize a matriz correspondente, a lista de métodos comuns/exclusivos e o resumo comparativo no final.

---

## 2. Classes consideradas

### 2.1 Connection

| Tipo | Classe | Arquivo | Observação |
|------|--------|---------|------------|
| **Facade/Strategy** | `Connection` | `src/Connection.php` | Delega para a strategy (engine). |
| **DB – Firebird** | `FirebirdConnection` | `src/Engine/FirebirdConnection.php` | |
| **DB – MySQL** | `MySQLiConnection` | `src/Engine/MySQLiConnection.php` | |
| **DB – OCI** | `OCIConnection` | `src/Engine/OCIConnection.php` | |
| **DB – ODBC** | `ODBCConnection` | `src/Engine/ODBCConnection.php` | |
| **DB – PDO** | `PDOConnection` | `src/Engine/PDOConnection.php` | |
| **DB – PostgreSQL** | `PgSQLConnection` | `src/Engine/PgSQLConnection.php` | |
| **DB – SQLite** | `SQLiteConnection` | `src/Engine/SQLiteConnection.php` | |
| **DB – SQL Server** | `SQLSrvConnection` | `src/Engine/SQLSrvConnection.php` | |
| **Flat – CSV** | `CSVConnection` | `src/Engine/CSVConnection.php` | |
| **Flat – INI** | `INIConnection` | `src/Engine/INIConnection.php` | |
| **Flat – JSON** | `JSONConnection` | `src/Engine/JSONConnection.php` | |
| **Flat – NEON** | `NEONConnection` | `src/Engine/NEONConnection.php` | |
| **Flat – XML** | `XMLConnection` | `src/Engine/XMLConnection.php` | |
| **Flat – YAML** | `YAMLConnection` | `src/Engine/YAMLConnection.php` | |

### 2.2 QueryBuilder

| Tipo | Classe | Arquivo | Observação |
|------|--------|---------|------------|
| **Facade/Strategy** | `QueryBuilder` | `src/QueryBuilder.php` | Delega para a strategy. |
| **DB** | `FirebirdQueryBuilder`, `MySQLiQueryBuilder`, `OCIQueryBuilder`, `ODBCQueryBuilder`, `PDOQueryBuilder`, `PgSQLQueryBuilder`, `SQLiteQueryBuilder`, `SQLSrvQueryBuilder` | `src/Engine/*QueryBuilder.php` | Mesma API (IQueryBuilder); |
| **Flat** | `CSVQueryBuilder`, `INIQueryBuilder`, `JSONQueryBuilder`, `NEONQueryBuilder`, `XMLQueryBuilder`, `YAMLQueryBuilder` | `src/Engine/*QueryBuilder.php` | Mesma API que as DB. |

---

## 3. Connection – Matriz de métodos públicos

Legenda: **Connection** = Facade; **DB** = engines tradicionais (Firebird, MySQLi, OCI, ODBC, PDO, PgSQL, SQLite, SQLSrv); **Flat** = as seis engines (CSV, INI, JSON, NEON, XML, YAML).
**Todas** as engines (DB e Flat) expõem exatamente **40 métodos públicos**; não há métodos exclusivos de engine. (PDO e ODBC usam `clearFetchCache` apenas como método privado, antes de query/prepare/exec.)

| Método | Connection | DB (8) | Flat (6) | Descrição sucinta |
|--------|:----------:|:------:|:--------:|-------------------|
| `__construct` | ✓ | ✓ | ✓ | Construtor. |
| `__call` | ✓ | ✓ | ✓ | Delega chamadas inacessíveis (objeto). |
| `__callStatic` | ✓ | ✓* | ✓* | Delega chamadas inacessíveis (estático). *Não em todas as engines. |
| `connect` | ✓ | ✓ | ✓ | Estabelece conexão. |
| `ping` | ✓ | ✓ | ✓ | Verifica se a conexão está ativa. |
| `disconnect` | ✓ | ✓ | ✓ | Desconecta. |
| `isConnected` | ✓ | ✓ | ✓ | Retorna se está conectado. |
| `getConnection` | ✓ | ✓ | ✓ | Obtém instância da conexão. |
| `setConnection` | ✓ | ✓ | ✓ | Define instância da conexão. |
| `beginTransaction` | ✓ | ✓ | ✓ | Inicia transação. |
| `commit` | ✓ | ✓ | ✓ | Confirma transação. |
| `rollback` | ✓ | ✓ | ✓ | Reverte transação. |
| `inTransaction` | ✓ | ✓ | ✓ | Indica se está em transação. |
| `lastInsertId` | ✓ | ✓ | ✓ | Último ID inserido (auto-increment). |
| `quote` | ✓ | ✓ | ✓ | Escapa string para SQL. |
| `getAllMetadata` | ✓ | ✓ | ✓ | Metadados da query (queryRows, affectedRows). |
| `setAllMetadata` | ✓ | ✓ | ✓ | Reseta metadados da query. |
| `getQueryString` | ✓ | ✓ | ✓ | String da query. |
| `setQueryString` | ✓ | ✓ | ✓ | Define string da query. |
| `getQueryParameters` | ✓ | ✓ | ✓ | Parâmetros da query. |
| `setQueryParameters` | ✓ | ✓ | ✓ | Define parâmetros da query. |
| `getQueryRows` | ✓ | ✓ | ✓ | Número de linhas da query. |
| `setQueryRows` | ✓ | ✓ | ✓ | Define número de linhas. |
| `getQueryColumns` | ✓ | ✓ | ✓ | Número de colunas. |
| `setQueryColumns` | ✓ | ✓ | ✓ | Define número de colunas. |
| `getAffectedRows` | ✓ | ✓ | ✓ | Linhas afetadas. |
| `setAffectedRows` | ✓ | ✓ | ✓ | Define linhas afetadas. |
| `getStatement` | ✓ | ✓ | ✓ | Statement atual. |
| `setStatement` | ✓ | ✓ | ✓ | Define statement. |
| `bindParam` | ✓ | ✓ | ✓ | Vincula parâmetro à query. |
| `parse` | ✓ | ✓ | ✓ | Analisa instrução SQL. |
| `query` | ✓ | ✓ | ✓ | Executa query. |
| `prepare` | ✓ | ✓ | ✓ | Prepara query. |
| `exec` | ✓ | ✓ | ✓ | Executa instrução (affected rows). |
| `fetch` | ✓ | ✓ | ✓ | Próxima linha. |
| `fetchAll` | ✓ | ✓ | ✓ | Todas as linhas. |
| `getAttribute` | ✓ | ✓ | ✓ | Atributo da conexão. |
| `setAttribute` | ✓ | ✓ | ✓ | Define atributo. |
| `errorCode` | ✓ | ✓ | ✓ | Código de erro. |
| `errorInfo` | ✓ | ✓ | ✓ | Informações do erro. |
| **`setStrategy`** | ✓ | — | — | **(Facade)** Define a strategy (engine). |
| **`getStrategy`** | ✓ | — | — | **(Facade)** Obtém a strategy. |

*Nota: Métodos como `mount`, `getTablePath`, `getSchema`, `load`, `save` etc. existem no **StructureHandler** de **cada uma** das seis engines Flat (CSV, INI, JSON, NEON, XML, YAML) e são usados apenas internamente. **Nenhuma** dessas Connections expõe esses métodos na API pública; todas delegam `__call` ao ArgumentsHandler, que (AbstractArguments) só trata `set*`/`get*` em propriedades. PDO e ODBC utilizam internamente um método privado `clearFetchCache` (antes de query/prepare/exec), não exposto na API.*

---

## 4. Connection – Métodos comuns (40 métodos)

Todos os métodos abaixo são compartilhados por **Connection** (Facade) e por **todas** as engines (DB e Flat), exceto os listados como exclusivos na seção 5.

| # | Método | Descrição |
|---|--------|-----------|
| 1 | `__construct` | Construtor da classe. |
| 2 | `__call` | Intercepta chamadas de métodos inacessíveis em contexto de objeto. |
| 3 | `__callStatic` | Intercepta chamadas de métodos inacessíveis em contexto estático. |
| 4 | `connect` | Estabelece conexão com o banco de dados ou recurso (arquivo/serviço). |
| 5 | `ping` | Verifica se a conexão está ativa. |
| 6 | `disconnect` | Desconecta do banco de dados. |
| 7 | `isConnected` | Retorna se está conectado. |
| 8 | `getConnection` | Obtém a instância da conexão. |
| 9 | `setConnection` | Define a instância da conexão. |
| 10 | `beginTransaction` | Inicia uma transação. |
| 11 | `commit` | Confirma a transação. |
| 12 | `rollback` | Reverte a transação. |
| 13 | `inTransaction` | Verifica se está em transação. |
| 14 | `lastInsertId` | Retorna o último ID inserido. |
| 15 | `quote` | Escapa uma string para uso em SQL. |
| 16 | `getAllMetadata` | Obtém todos os metadados (queryRows, affectedRows). |
| 17 | `setAllMetadata` | Reseta os metadados da query. |
| 18 | `getQueryString` | Obtém a string da query. |
| 19 | `setQueryString` | Define a string da query. |
| 20 | `getQueryParameters` | Obtém os parâmetros da query. |
| 21 | `setQueryParameters` | Define os parâmetros da query. |
| 22 | `getQueryRows` | Obtém o número de linhas da query. |
| 23 | `setQueryRows` | Define o número de linhas. |
| 24 | `getQueryColumns` | Obtém o número de colunas. |
| 25 | `setQueryColumns` | Define o número de colunas. |
| 26 | `getAffectedRows` | Obtém o número de linhas afetadas. |
| 27 | `setAffectedRows` | Define o número de linhas afetadas. |
| 28 | `getStatement` | Obtém o statement. |
| 29 | `setStatement` | Define o statement. |
| 30 | `bindParam` | Vincula um parâmetro à query. |
| 31 | `parse` | Analisa uma instrução SQL. |
| 32 | `query` | Executa uma query SQL. |
| 33 | `prepare` | Prepara uma query. |
| 34 | `exec` | Executa uma instrução SQL. |
| 35 | `fetch` | Busca a próxima linha. |
| 36 | `fetchAll` | Busca todas as linhas. |
| 37 | `getAttribute` | Obtém um atributo. |
| 38 | `setAttribute` | Define um atributo. |
| 39 | `errorCode` | Obtém o código de erro. |
| 40 | `errorInfo` | Obtém informações do erro. |

---

## 5. Connection – Métodos exclusivos

### 5.1 Facade/Strategy (Connection.php)

| Método | Assinatura | Descrição |
|--------|------------|-----------|
| `setStrategy` | `setStrategy(IConnection $strategy): void` | Define a instância da estratégia (engine). |
| `getStrategy` | `getStrategy(): IConnection` | Obtém a instância da estratégia (engine). |

### 5.2 Flat File – StructureHandler (uso interno em **todas** as 6 engines; nenhum método na API da Connection)

Os métodos abaixo existem no **StructureHandler** de **cada uma** das seis engines Flat (CSV, INI, JSON, NEON, XML, YAML) e são usados **apenas internamente** (ex.: `preConnect()` chama `getStructureHandler()->mount()`). **Nenhuma** classe *Connection* Flat expõe esses métodos na API pública: CSVConnection, INIConnection, JSONConnection, NEONConnection, XMLConnection e YAMLConnection usam todas o mesmo fluxo `__call` → ArgumentsHandler → `AbstractArguments::call()`, que só trata `set*`/`get*` em propriedades da instância. Por isso **todas** as seis têm exatamente **40 métodos públicos** e **0 exclusivos**.

| Método (interno ao StructureHandler) | Descrição |
|--------------------------------------|-----------|
| `mount` | Monta e retorna a estrutura do banco. |
| `getTablePath` | Caminho do arquivo da tabela. |
| `getSchema` / `getSchemaFile` / `getSchemaData` | Schema do banco. |
| `getTables` / `setTables` | Lista de tabelas. |
| `getStructure` / `setStructure` | Estrutura do banco. |
| `load` / `save` | Carrega/salva dados em arquivo. |
| `getData` / `setData` | Dados em memória. |
| `from` / `getCurrentTable` | Tabela ativa. |
| `insert` / `update` / `delete` / `selectWhere` | CRUD direto. |
| `getFetchedRows` / `setFetchedRows` | Linhas buscadas. |

---

## 6. QueryBuilder – Matriz de métodos públicos

Legenda: **QueryBuilder** = Facade; **DB** = 8 engines; **Flat** = 6 engines. Todas respeitam apenas a interface IQueryBuilder.

| Método | QueryBuilder (Facade) | DB (8) | Flat (6) | Descrição sucinta |
|--------|:--------------------:|:------:|:--------:|-------------------|
| `__construct` | ✓ | ✓ | ✓ | Construtor (contexto IConnection). |
| `with` | ✓ | ✓ | ✓ | Inicializador estático com contexto de conexão. |
| `setStrategy` | ✓ | — | — | **(Facade)** Define a strategy. |
| `getStrategy` | ✓ | — | — | **(Facade)** Obtém a strategy. |
| `select` | ✓ | ✓ | ✓ | Cláusula SELECT. |
| `distinct` | ✓ | ✓ | ✓ | DISTINCT. |
| `from` | ✓ | ✓ | ✓ | FROM. |
| `join` | ✓ | ✓ | ✓ | JOIN. |
| `selfJoin` | ✓ | ✓ | ✓ | SELF JOIN. |
| `leftJoin` | ✓ | ✓ | ✓ | LEFT JOIN. |
| `rightJoin` | ✓ | ✓ | ✓ | RIGHT JOIN. |
| `innerJoin` | ✓ | ✓ | ✓ | INNER JOIN. |
| `outerJoin` | ✓ | ✓ | ✓ | OUTER JOIN. |
| `crossJoin` | ✓ | ✓ | ✓ | CROSS JOIN. |
| `on` | ✓ | ✓ | ✓ | ON (join). |
| `andOn` | ✓ | ✓ | ✓ | AND ON. |
| `orOn` | ✓ | ✓ | ✓ | OR ON. |
| `where` | ✓ | ✓ | ✓ | WHERE. |
| `andWhere` | ✓ | ✓ | ✓ | AND WHERE. |
| `orWhere` | ✓ | ✓ | ✓ | OR WHERE. |
| `having` | ✓ | ✓ | ✓ | HAVING. |
| `andHaving` | ✓ | ✓ | ✓ | AND HAVING. |
| `orHaving` | ✓ | ✓ | ✓ | OR HAVING. |
| `group` | ✓ | ✓ | ✓ | GROUP BY. |
| `order` | ✓ | ✓ | ✓ | ORDER BY. |
| `orderAsc` | ✓ | ✓ | ✓ | ORDER BY ASC. |
| `orderDesc` | ✓ | ✓ | ✓ | ORDER BY DESC. |
| `limit` | ✓ | ✓ | ✓ | LIMIT. |
| `union` | ✓ | ✓ | ✓ | UNION. |
| `unionAll` | ✓ | ✓ | ✓ | UNION ALL. |
| `whereExists` | ✓ | ✓ | ✓ | WHERE EXISTS. |
| `whereNotExists` | ✓ | ✓ | ✓ | WHERE NOT EXISTS. |
| `build` | ✓ | ✓ | ✓ | Monta a string SQL final. |
| `buildRaw` | ✓ | ✓ | ✓ | Monta a string SQL bruta. |
| `getValues` | ✓ | ✓ | ✓ | Valores a serem vinculados. |
| `getAllMetadata` | ✓ | ✓ | ✓ | Metadados da query. |
| `fetch` | ✓ | ✓ | ✓ | Executa e retorna uma linha. |
| `fetchAll` | ✓ | ✓ | ✓ | Executa e retorna todas as linhas. |

---

## 7. QueryBuilder – Métodos comuns (36 métodos)

Compartilhados pela **Facade** e por **todas** as especializadas (DB e Flat), exceto `setStrategy`/`getStrategy` (apenas Facade).

| # | Método | Descrição |
|---|--------|-----------|
| 1 | `__construct` | Inicializa o builder com contexto de conexão (opcional). |
| 2 | `with` | Inicializador estático com contexto (IConnection). |
| 3 | `select` | Especifica colunas do SELECT. |
| 4 | `distinct` | Resultados distintos. |
| 5 | `from` | Tabela(s) do FROM. |
| 6 | `join` | Junção genérica. |
| 7 | `selfJoin` | Auto-junção. |
| 8 | `leftJoin` | LEFT JOIN. |
| 9 | `rightJoin` | RIGHT JOIN. |
| 10 | `innerJoin` | INNER JOIN. |
| 11 | `outerJoin` | OUTER JOIN. |
| 12 | `crossJoin` | CROSS JOIN. |
| 13 | `on` | Condição ON do join. |
| 14 | `andOn` | AND ON. |
| 15 | `orOn` | OR ON. |
| 16 | `where` | Condição WHERE. |
| 17 | `andWhere` | AND WHERE. |
| 18 | `orWhere` | OR WHERE. |
| 19 | `having` | HAVING. |
| 20 | `andHaving` | AND HAVING. |
| 21 | `orHaving` | OR HAVING. |
| 22 | `group` | GROUP BY. |
| 23 | `order` | ORDER BY. |
| 24 | `orderAsc` | ORDER BY ASC. |
| 25 | `orderDesc` | ORDER BY DESC. |
| 26 | `limit` | LIMIT. |
| 27 | `union` | UNION. |
| 28 | `unionAll` | UNION ALL. |
| 29 | `whereExists` | WHERE EXISTS (subquery). |
| 30 | `whereNotExists` | WHERE NOT EXISTS (subquery). |
| 31 | `build` | Constrói a string SQL. |
| 32 | `buildRaw` | Constrói a string SQL bruta. |
| 33 | `getValues` | Valores para bind. |
| 34 | `getAllMetadata` | Metadados da query. |
| 35 | `fetch` | Executa e busca uma linha. |
| 36 | `fetchAll` | Executa e busca todas as linhas. |

---

## 8. QueryBuilder – Métodos exclusivos

Apenas a **Facade** possui métodos exclusivos (não declarados em IQueryBuilder):

| Método | Assinatura | Descrição |
|--------|------------|-----------|
| `setStrategy` | `setStrategy(IQueryBuilder $strategy): void` | Define a strategy (engine) do query builder. |
| `getStrategy` | `getStrategy(): IQueryBuilder` | Obtém a strategy atual. |

---

## 9. Resumo comparativo

### 9.1 Connection

| Grupo | Total de métodos | Comuns | Exclusivos |
|-------|:----------------:|:------:|:----------:|
| **Connection (Facade)** | 42 | 40 | 2 (`setStrategy`, `getStrategy`) |
| **Engines DB (8)** | 40 | 40 | 0 |
| **Engines Flat (6): CSV, INI, JSON, NEON, XML, YAML** | 40 | 40 | 0 |

Todas as seis Connections Flat têm a mesma contagem: **40 métodos totais**, **40 comuns**, **0 exclusivos**. Não há linha separada para JSONConnection: ela está incluída nas “Engines Flat (6)” com as demais.

**Contagem geral Connection:**
- **Métodos comuns:** 40
- **Exclusivos Facade:** 2
- **Exclusivos engines:** 0 (interface consistente; PDO/ODBC usam `clearFetchCache` como método privado)
- **Flat Files (todas as 6):** 40 métodos, 0 exclusivos

### 9.2 QueryBuilder

| Grupo | Total de métodos | Comuns | Exclusivos |
|-------|:----------------:|:------:|:----------:|
| **QueryBuilder (Facade)** | 38 | 36 | 2 (`setStrategy`, `getStrategy`) |
| **Especializadas (DB + Flat)** | 38 | 36 | 0 |

Todas as implementações respeitam apenas a interface IQueryBuilder.

**Contagem geral QueryBuilder:**
- **Métodos comuns:** 36
- **Exclusivos Facade:** 2
- **Exclusivos especializadas:** 0

---

## 10. Verificação de interfaces

### 10.1 Connection

| Interface | Declara | Connection (Facade) | Engines |
|-----------|--------|:-------------------:|:------:|
| **IConnection** | 38 métodos (connect, ping, disconnect, isConnected, get/setConnection, beginTransaction, commit, rollback, inTransaction, lastInsertId, quote, getAllMetadata, setAllMetadata, get/setQueryString, get/setQueryParameters, get/setQueryRows, get/setQueryColumns, get/setAffectedRows, get/setStatement, bindParam, parse, prepare, query, exec, fetch, fetchAll, get/setAttribute, errorCode, errorInfo) | Implementa e delega para strategy | Todas implementam (DB e Flat). |
| **IConnectionStrategy** | `setStrategy(IConnection)`, `getStrategy(): IConnection` | Implementa | Não implementam (são a strategy). |

**Conformidade:**
- A **Facade** implementa `IConnection` e `IConnectionStrategy`; delega todas as operações de `IConnection` para a strategy.
- As **engines** implementam `IConnection` (e algumas também outras interfaces, ex.: IFetch, IStatements, IDSN, IArguments, ITransactions).
- `__construct`, `__call` e `__callStatic` não fazem parte de `IConnection`; são detalhes de implementação.
- PDO e ODBC implementam a interface opcional **IFetchCache** (`clearFetchCache(): void`); o StatementsHandler chama quando a conexão é `instanceof IFetchCache`, sem `method_exists`.

### 10.2 QueryBuilder

| Interface | Declara | QueryBuilder (Facade) | Especializadas |
|-----------|--------|:--------------------:|:--------------:|
| **IQueryBuilder** | select, distinct, from, join, selfJoin, left/right/inner/outer/crossJoin, on, andOn, orOn, where, andWhere, orWhere, having, andHaving, orHaving, group, order, orderAsc, orderDesc, limit, union, unionAll, whereExists, whereNotExists, build, buildRaw, getValues, getAllMetadata, fetch, fetchAll (estáticos onde aplicável) | Implementa e delega para strategy | Todas implementam. |
| **IQueryBuilderStrategy** | `setStrategy(IQueryBuilder)`, `getStrategy(): IQueryBuilder` | Implementa | Não implementam. |

**Conformidade:**
- A **Facade** implementa `IQueryBuilder` e `IQueryBuilderStrategy`; delega para a strategy.
- As **especializadas** implementam `IQueryBuilder`.
- `__construct` e `with` não estão em `IQueryBuilder`; são detalhes de implementação comuns a Facade e especializadas.

---

## 11. Referência rápida de arquivos

| Papel | Interface | Facade | Engines |
|-------|-----------|--------|---------|
| Conexão | `Interfaces/IConnection.php`, `Interfaces/Strategy/IConnectionStrategy.php`, opcional: `Interfaces/Connection/IFetchCache.php` (PDO, ODBC) | `Connection.php` | `Engine/*Connection.php` |
| QueryBuilder | `Interfaces/IQueryBuilder.php`, `Interfaces/Strategy/IQueryBuilderStrategy.php` | `QueryBuilder.php` | `Engine/*QueryBuilder.php` |

---

*Para nova análise ou inclusão de engines, atualize as seções 2–9 e o resumo (seção 10) conforme a lista de classes e métodos públicos reais.*
