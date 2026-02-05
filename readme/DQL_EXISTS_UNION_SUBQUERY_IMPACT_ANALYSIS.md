# Análise de Impacto: EXISTS, UNION/UNION ALL e Queries Aninhadas (DQL)

## Objetivo

Avaliar o impacto de implementar suporte às cláusulas **EXISTS**, **UNION**/ **UNION ALL** e a **queries aninhadas (subqueries)** para SELECT/DQL, de forma compatível com todas as engines (incluindo Flat Files), tanto na **Connection** quanto no **QueryBuilder**.

---

## 1. Estado Atual do Código

### 1.1 Interfaces e Estruturas

| Componente | Responsabilidade | Suporte atual |
|------------|------------------|----------------|
| **IQueryBuilder** | Contrato do QueryBuilder (select, from, join, where, having, group, order, limit) | Não define `exists`, `union`, `unionAll` nem subquery. |
| **IClause** | Contrato das cláusulas (select, from, join, on, makeWhere, where, makeHaving, having, group, order, limit) | Não define cláusulas para EXISTS, UNION ou subquery. |
| **QueryObject** | Armazena estado da query (select, from, join, on, where, having, group, order, limit) | Não possui propriedades `union`, `unionAll`, nem representação de subquery em FROM/WHERE. |
| **Core Where** | Enum de tipos de agregação em WHERE/HAVING | **EXISTS já existe** no enum (`Where::EXISTS()`), mas não é tratado em Criteria nem em Builder. |
| **Criteria::getWhereHaving** | Monta estrutura de condição (IN, LIKE, BETWEEN, NONE) | Não detecta nem monta estrutura para EXISTS (subquery). |
| **Builder::buildWhere** (PDO/SQL) | Gera SQL de WHERE | Trata apenas NONE, BETWEEN, IN, LIKE; **não trata EXISTS**. |
| **DataProcessor** (Flat Files) | Filtros, ordenação, projeção em memória | Suporta WHERE com =, IN, LIKE, BETWEEN, IS NULL; **não suporta EXISTS nem subquery**. Não há UNION. |

### 1.2 Engines Envolvidas

- **SQL:** Firebird, MySQLi, OCI, PgSQL, SQLite, SQLSrv, PDO, ODBC — cada uma com `Connection` + `QueryBuilder` (Builder, Clause, Criteria, Regex).
- **Flat Files:** JSON, CSV, XML, YAML, INI, NEON — usam `AbstractFlatFileStatements`, `DataProcessor` e QueryBuilder que gera SQL e/ou executa em memória.

### 1.3 Connection vs QueryBuilder

- **Connection:** Expõe `query(mixed ...$params)` que delega para o Statements da engine. Não expõe `getQueryBuilder()` na interface; o uso típico é `QueryBuilder::with($connection)`.
- **QueryBuilder:** Usa Strategy por engine; os métodos estáticos (select, from, where, etc.) delegam para a strategy. Para EXISTS/UNION/subquery, será necessário estender interface, QueryObject, Clause, Criteria, Regex e Builder de **todas** as engines.

---

## 2. Impacto por Funcionalidade

### 2.1 Cláusula EXISTS

**Semântica:** `WHERE [NOT] EXISTS (subquery)` — retorna linhas para as quais a subquery retorna ao menos uma linha.

#### 2.1.1 Alterações Necessárias

| Camada | Alteração | Complexidade |
|--------|-----------|--------------|
| **IQueryBuilder** | Novo método: `exists(string\|IQueryBuilder $subquery, bool $negate = false)` ou `whereExists(...)` / `whereNotExists(...)`. | Baixa |
| **IClause** | Novo método: `exists(array $arguments)` ou tratar EXISTS dentro de `makeWhere` (argumentos com tipo EXISTS + subquery). | Baixa |
| **QueryObject** | WHERE já é array; cada item pode ganhar `aggregation.type = Where::EXISTS()` e `arguments.subquery` (string ou referência a outro QueryObject). | Média |
| **Criteria (SQL)** | Em `getWhereHaving`: detectar padrão EXISTS (e NOT EXISTS), definir `aggregation.type = Where::EXISTS()`, guardar subquery (string ou build de IQueryBuilder). Regex pode precisar de novo padrão para `EXISTS (SELECT ...)`. | Média |
| **Builder (SQL)** | Em `buildWhere`: no `match` de `aggregation.type`, tratar `Where::EXISTS()` gerando `EXISTS (subquery)` ou `NOT EXISTS (subquery)`; subquery pode ser string ou resultado de outro Builder. Placeholders da subquery devem ser mesclados em `getValues()`. | Média |
| **Builder (Flat Files)** | Para “executar” EXISTS: interpretar a subquery (tabela/arquivo, WHERE, etc.), executar em memória (DataProcessor ou fluxo equivalente) e retornar booleano (há linhas ou não). Exige **parser/executor de subquery** ou API que receba subquery já resolvida. | **Alta** |
| **DataProcessor** | Não há conceito nativo de subquery. Opções: (1) adicionar condição especial `EXISTS` cujo valor seja um callable que executa a subquery e retorna bool; (2) ou deixar apenas para SQL gerado (build()) e em `query()` bruto com EXISTS tratar via parser. | **Alta** |
| **Connection** | `query('SELECT ... WHERE EXISTS (SELECT ...)')` já funciona em engines SQL. Em Flat Files, o StatementsHandler precisaria interpretar EXISTS (parse + executar subquery) se quisermos suporte transparente. | Média (SQL: zero; Flat: alta) |

#### 2.1.2 Compatibilidade por Engine

- **Firebird, MySQL, OCI, PgSQL, SQLite, SQLSrv, PDO, ODBC:** Suporte nativo a EXISTS. Impacto limitado a QueryBuilder (Criteria + Builder + getValues).
- **JSON, CSV, XML, YAML, INI, NEON:** Sem SQL nativo; EXISTS exige executar subquery em memória (múltiplas tabelas/arquivos, escopo). Impacto alto.

---

### 2.2 Cláusulas UNION e UNION ALL

**Semântica:** `(SELECT ...) UNION (SELECT ...)` — concatena resultados (UNION remove duplicatas; UNION ALL mantém).

#### 2.2.1 Alterações Necessárias

| Camada | Alteração | Complexidade |
|--------|-----------|--------------|
| **IQueryBuilder** | Novos métodos: `union(string\|IQueryBuilder $query)` e `unionAll(string\|IQueryBuilder $query)`. | Baixa |
| **IClause** | Novos métodos: `union(array $arguments)`, `unionAll(array $arguments)`. | Baixa |
| **QueryObject** | Novas propriedades: `union` e `unionAll` (arrays de queries: string ou referência a IQueryBuilder). **QueryObject** hoje não lista `union`/`unionAll` em `$validProperties` — é preciso adicionar. | Média |
| **Builder (SQL)** | Em `buildQuery()`: após montar o SELECT principal, iterar `query->union` e `query->unionAll`, concatenando ` UNION ` ou ` UNION ALL ` + build de cada query. Cada query pode ser string ou outro Builder; placeholders devem ser mesclados em `getValues()`. Número e tipo de colunas devem ser compatíveis (validação opcional). | Média |
| **Builder (Flat Files)** | Executar cada SELECT (principal + union + unionAll) via DataProcessor (ou equivalente), concatenar resultados. UNION = remover duplicatas (comparação de linhas); UNION ALL = manter todas. Estrutura de colunas deve ser compatível entre as queries. | **Alta** |
| **DataProcessor** | Não possui `union`/`unionAll`. Seria necessário: método que receba dois conjuntos de dados e concatene, com opção de distinct (UNION) ou não (UNION ALL). | Média |
| **Connection** | `query('SELECT ... UNION SELECT ...')` funciona em engines SQL. Em Flat Files, o handler teria que parsear múltiplos SELECTs e executar em sequência, depois concatenar. | Média (SQL: zero; Flat: alta) |

#### 2.2.2 Compatibilidade por Engine

- **Engines SQL:** Suporte nativo a UNION e UNION ALL. Impacto no QueryBuilder (QueryObject, Clause, Builder, getValues).
- **Flat Files:** Implementação em memória (várias “tabelas”/arquivos, mesma estrutura de colunas). Impacto alto no fluxo de execução e no Builder.

---

### 2.3 Queries Aninhadas (Subqueries)

**Semântica:** Subquery em FROM (`FROM (SELECT ...) AS t`), em WHERE (`WHERE col IN (SELECT ...)`, `WHERE EXISTS (SELECT ...)`), ou em SELECT (`SELECT (SELECT ...) AS col`).

#### 2.3.1 Tipos e Impacto

| Tipo | Exemplo | Onde afeta |
|------|---------|------------|
| **FROM (subquery)** | `SELECT * FROM (SELECT a, b FROM t1) AS sub` | FROM hoje aceita tabelas; precisaria aceitar `IQueryBuilder` ou string SQL. QueryObject.from[] teria tipo “subquery”. Builder SQL gera `FROM (subquery) AS alias`. Flat: executar subquery primeiro e usar resultado como “tabela” em memória. |
| **WHERE IN (subquery)** | `WHERE id IN (SELECT id FROM t2)` | Hoje IN usa lista de valores. Precisaria aceitar subquery (string ou IQueryBuilder). Criteria e Builder gerariam `IN (SELECT ...)`. Flat: executar subquery, obter lista, aplicar IN. |
| **WHERE EXISTS (subquery)** | Já coberto em 2.1. | — |
| **SELECT (subquery)** | `SELECT (SELECT name FROM t2 WHERE id = t1.id) AS name FROM t1` | Coluna no SELECT pode ser subquery escalar. Clause select e Builder precisariam tratar tipo “subquery”; Flat: para cada linha, executar subquery (custoso). |

#### 2.3.2 Alterações Necessárias

| Camada | Alteração | Complexidade |
|--------|-----------|--------------|
| **IQueryBuilder / IClause** | `from()` aceitar subquery (string ou IQueryBuilder). `where()` / Criteria aceitar IN (subquery). `select()` aceitar expressão escalar com subquery. | Média |
| **QueryObject** | FROM com tipo subquery; WHERE com IN (subquery); SELECT com coluna tipo subquery. | Média |
| **Criteria / Regex** | Parsing de subquery dentro de FROM, WHERE IN, EXISTS, SELECT. Regex e estrutura de dados mais complexos. | **Alta** |
| **Builder (SQL)** | buildFrom/buildWhere/buildSelect gerando subquery; mesclagem de placeholders de todas as subqueries. | **Alta** |
| **Builder (Flat Files)** | Ordem de execução: subqueries primeiro, depois query externa; múltiplas fontes (arquivos/tabelas). | **Muito alta** |
| **DataProcessor** | Não foi desenhado para subqueries. FROM (subquery) = resultado como dataset; IN (subquery) = lista vinda de outra “query”; EXISTS = já discutido. | **Muito alta** |
| **Connection** | SQL bruto com subqueries funciona em SQL. Flat Files exigem interpretação completa. | Alta (Flat) |

---

## 3. Resumo de Impacto por Camada

| Camada | EXISTS | UNION/UNION ALL | Subqueries (FROM/WHERE/SELECT) |
|--------|--------|------------------|---------------------------------|
| **IQueryBuilder** | +método(s) | +2 métodos | Estender from/where/select |
| **IClause** | +exists ou makeWhere | +2 métodos | Estender from, makeWhere, select |
| **QueryObject** | where[] com tipo EXISTS | +union, unionAll | from/where/select com tipo subquery |
| **Core (Where, etc.)** | Já existe enum | N/A | N/A |
| **Criteria + Regex (por engine SQL)** | Detectar EXISTS, subquery | N/A (union no Builder) | Parsing subquery IN/EXISTS/FROM |
| **Builder SQL (por engine)** | buildWhere EXISTS, getValues | buildQuery union, getValues | buildFrom/buildWhere/buildSelect subquery |
| **Builder Flat (por engine)** | Executar subquery, bool | Executar N queries, concatenar | Ordem execução, múltiplas fontes |
| **DataProcessor** | EXISTS como “condição especial” | union/unionAll de datasets | Não desenhado; mudança grande |
| **Connection** | Transparente em SQL | Transparente em SQL | Transparente em SQL |
| **Statements Flat (query bruta)** | Parser + executor EXISTS | Parser múltiplos SELECTs + concat | Parser completo de subqueries |

---

## 4. Recomendações

### 4.1 Ordem de Implementação Sugerida

1. **UNION / UNION ALL** — Não depende de parser de subquery; apenas nova estrutura em QueryObject + Builder (SQL e Flat). Em Flat Files, DataProcessor ganha “concatenação de resultados” e o Builder executa cada query e concatena.
2. **EXISTS** — Enum já existe; falta Criteria, Builder SQL e getValues. Em Flat Files, definir bem o contrato (ex.: subquery como string parseável ou como callable que retorna bool).
3. **Subqueries** — Maior esforço: FROM/WHERE IN/SELECT com subquery; depois integrar EXISTS como caso de subquery em WHERE.

### 4.2 Compatibilidade com Flat Files

- **Opção A (mínima):** EXISTS, UNION e subqueries apenas no **build()** (geração de SQL). Execução em Flat Files continua sem essas cláusulas; `query()` com SQL bruto contendo EXISTS/UNION/subquery pode retornar erro ou “não suportado” para Flat Files.
- **Opção B (paridade):** Implementar em Flat Files: (1) UNION/UNION ALL via DataProcessor + Builder; (2) EXISTS executando subquery em memória (parser simples ou API com callable); (3) subqueries limitadas (ex.: apenas FROM e IN) com execução em ordem e múltiplos arquivos. Exige esforço alto e bem definição de escopo (quais subqueries são suportadas).

### 4.3 Connection

- Nenhuma alteração obrigatória na interface **IConnection** para EXISTS/UNION/subquery: o uso é via `query($sql)` ou `QueryBuilder::with($connection)->select(...)->...->fetchAll()`.
- Se no futuro existir `getQueryBuilder()` na Connection, ele apenas retornaria o QueryBuilder já configurado com a strategy da engine; a nova funcionalidade continua no QueryBuilder.

---

## 5. Conclusão

- **EXISTS:** Impacto **médio** em engines SQL (Criteria, Builder, getValues; enum já existe). Impacto **alto** em Flat Files (execução de subquery em memória).
- **UNION / UNION ALL:** Impacto **médio** em todas as engines (QueryObject, Clause, Builder; em Flat Files, DataProcessor + Builder para concatenar resultados).
- **Queries aninhadas:** Impacto **alto** em SQL (parsing e geração de subquery em FROM/WHERE/SELECT) e **muito alto** em Flat Files (ordem de execução, múltiplas fontes, possível extensão do DataProcessor ou de um executor dedicado).

Para compatibilidade **completa** entre Connection e QueryBuilder em **todas** as engines (incluindo Flat Files), é necessário estender interfaces, QueryObject, Clause, Criteria, Regex e Builder em cada engine, além de definir comportamento e escopo de execução para Flat Files (EXISTS, UNION, subqueries). A opção de implementar primeiro apenas para engines SQL e deixar Flat Files com build() sem execução dessas cláusulas reduz o escopo e o risco inicial.
