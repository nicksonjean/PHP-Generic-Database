# Análise: Parser SQL Unificado e Arquitetura por Dialetos

**Objetivo:** Recomendar a melhor abordagem para refatorar o código do projeto, criando um parser SQL que suporte o léxico de diversos dialetos (SQLite, MySQL, Oracle, PostgreSQL, SQL Server, Firebird), facilitando a expansão das classes QueryBuilder, mantendo o suporte a queries puras já funcional.

**Última atualização:** 2026-03-07

---

## 1. Estado atual: fragmentação e volatilidade

### 1.1 Onde está o “parse SQL” hoje

| Componente | Localização | Responsabilidade | Problema |
|------------|-------------|------------------|----------|
| **Lexicon** | `Helpers/Parsers/SQL/Lexicon.php` | Palavras reservadas SQL (lista única) | Única lista; não diferencia dialetos (ex.: `LIMIT` vs `FETCH FIRST n ROWS`, `TOP`, `ROWNUM`). |
| **Parse** | `Helpers/Parsers/SQL/Parse.php` | Escape de identificadores, binds (`:name`/`?`/`$n`), extração de parâmetros | Dialeto é um `int` (BACKTICK, DOUBLE_QUOTE, etc.); lógica de escape e regex espalhada e frágil. |
| **Analyser** | `Helpers/Parsers/SQL/Analyser.php` | Análise AST via Hoa Compiler (LL(k)); extração de tabelas, colunas, parâmetros, subqueries, EXISTS | Cache estático de gramática por path; construtor recebe `string $sql` + `?string $grammarPath`; suporta gramáticas customizadas e regras alternativas via terceiro parâmetro. |
| **TypeDetector** | `Helpers/Parsers/SQL/Query/TypeDetector.php` | Detecção de tipo (SELECT/INSERT/UPDATE/DELETE), CTE, subqueries | Regex e comentários hardcoded; não considera diferenças de sintaxe por dialeto. |
| **Info** | `Helpers/Parsers/SQL/Query/Info.php` | DTO com resultado da análise da query | Depende do TypeDetector; sem noção de dialeto. |
| **FlatFile/SelectParser** | `Helpers/Parsers/SQL/FlatFile/SelectParser.php` | Helpers para Flat Files: split UNION/UNION ALL, extração de EXISTS do WHERE | Exclusivo para engines Flat File (CSV, JSON, XML, YAML, INI, NEON). |
| **Regex (RDBMS)** | `Engine/{SQLite,MySQLi,PgSQL,OCI,Firebird,ODBC,PDO,SQLSrv}/QueryBuilder/Regex.php` | Padrões para SELECT, FROM, ON, WHERE/HAVING, GROUP/ORDER, LIMIT | **Código praticamente idêntico** entre engines (copy-paste); cada engine tem sua classe. |
| **Regex (FlatFiles)** | `Engine/{JSON,CSV,XML,YAML,INI,NEON}/QueryBuilder/Regex.php` | Implementam `IRegex` com padrões próprios | Outra “família” de regex; fragmentação duplicada. |
| **Criteria** | `Engine/*/QueryBuilder/Criteria.php` | Parse de cláusulas (getSelect, getFrom, getWhereHaving, etc.) usando `Regex` da engine | **Lógica duplicada** em cada engine; pequenas variações. |
| **Clause** | `Engine/*/QueryBuilder/Clause.php` | Montagem de cláusulas a partir de entrada do usuário | Duplicação por engine. |
| **Builder** | `Engine/*/QueryBuilder/Builder.php` | Montagem da string SQL e execução (FlatFiles) ou apenas build (RDBMS) | Builders FlatFiles com ~1000 linhas cada, muito similares; chamadas diretas a `Parse::escape()` com dialect fixo (ex.: `SQL_DIALECT_DOUBLE_QUOTE`). |

Conclusão: o “parse SQL” está **fragmentado** em várias camadas (Lexicon, Parse, TypeDetector, Regex por engine, Criteria por engine) e **granularizado** em muitas classes (uma por engine), com duplicação massiva e sem um modelo claro por dialeto.

### 1.2 Pontos de fragilidade e volatilidade

- **Alterar uma regra de escape ou um padrão** exige tocar em vários pontos (Parse, possivelmente Lexicon, e cada Builder que chama `Parse::escape`).
- **Adicionar um novo dialeto** (ex.: novo banco) implica criar nova engine, copiar Regex/Criteria/Clause/Builder e ajustar dialect em StatementsHandler/QueryBuilder.
- **Diferenças reais entre dialetos** (identificadores, placeholders, LIMIT/OFFSET, comentários) estão misturadas com constantes numéricas e regex genéricas, sem um único “perfil” por dialeto.
- **Queries puras** já funcionam porque o motor SQL do banco executa a string; a fragilidade está na **camada de análise/escape/binding** usada antes de enviar ao driver e na **duplicação** ao expandir QueryBuilders.

---

## 2. Recomendações de alto nível

### 2.1 Objetivo da refatoração

1. **Parser SQL unificado** que suporte múltiplos dialetos (SQLite, MySQL, Oracle, PostgreSQL, SQL Server, Firebird) em um único ponto de configuração (léxico, delimitadores, placeholders, regras de escape).
2. **Uma única “fonte de verdade”** para: escape de identificadores, binding de parâmetros, detecção de tipo de query, e (onde fizer sentido) padrões de regex para cláusulas.
3. **QueryBuilders** que dependam do **dialeto** (ou da connection), e não de uma classe Regex/Criteria por engine, reduzindo duplicação e facilitando expansão.

### 2.2 Manter intacto

- **Suporte a queries puras** via `Connection::query()` e `Connection::prepare()`: continuam sendo enviadas ao motor (RDBMS ou emulação FlatFiles) após eventual escape/binding; a refatoração deve **preservar** esse fluxo e apenas **alimentá-lo** a partir do novo parser/dialeto.
- **Contratos públicos** (interfaces de Connection, QueryBuilder, IBuilder, IRegex, etc.) podem ser mantidos ou evoluídos de forma compatível.

---

## 3. Melhor abordagem: Strategy + Dialect Profile (e opcionalmente Abstract Factory)

### 3.1 Design pattern recomendado: **Strategy + Dialect Profile**

- **Strategy:** O “comportamento” de parsing (escape, binding, tipo de query, e eventualmente padrões de cláusulas) varia por dialeto. Cada dialeto é uma **estratégia** (classe ou configuração) que implementa a mesma interface.
- **Dialect Profile:** Um **perfil por dialeto** (SQLite, MySQL, Oracle, PostgreSQL, SQL Server, Firebird) centraliza:
  - Caractere de quote de identificador (backtick, aspas duplas, etc.).
  - Formato de placeholder (`?`, `:name`, `$1`, `$n`, etc.).
  - Palavras reservadas (ou extensão da lista base) e regras de comentário.
  - Regras específicas (ex.: LIMIT/OFFSET vs `FETCH FIRST n ROWS ONLY` vs `TOP (n)` vs `ROWNUM`).

Assim, o parser unificado **não precisa de dezenas de classes**; precisa de **uma API que recebe o dialeto** (ou a strategy do dialeto) e aplica as regras corretas.

### 3.2 Onde cada pattern se encaixa

| Necessidade | Pattern / abordagem |
|-------------|---------------------|
| Variação de comportamento por banco/dialeto | **Strategy**: `SqlDialectInterface` com implementações `MySQLDialect`, `PostgreSQLDialect`, etc. |
| Configuração centralizada por dialeto | **Dialect profile**: objeto ou array com quote char, bind style, reserved words, regex base. |
| Criar família de parser/lexer por engine | **Abstract Factory** (opcional): `DialectFactory::createForSqlite()` retorna parser + lexer configurados para SQLite. |
| Consumidores que não precisam saber o dialeto | **Facade**: `SqlParser::escape($sql, $dialect)` e `SqlParser::detectType($sql, $dialect)` escondem a strategy. |
| Evoluir para AST no futuro | **Visitor** (fase posterior): árvore sintática visitada por “emissor” por dialeto. |

Recomendação imediata: **Strategy + Dialect Profile**; Facade para a API pública. Abstract Factory só se quiser encapsular a criação por tipo de connection.

---

## 4. Arquitetura sugerida do parser unificado

### 4.1 Camadas

```
┌─────────────────────────────────────────────────────────────────┐
│  Consumidores: Connection, QueryBuilder, StatementsHandler       │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│  Facade: SqlParser (escape, binding, parseParameters, detectType)  │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│  Strategy: SqlDialectInterface (getQuoteChar, getBindStyle,        │
│            getReservedWords, getCommentPatterns, getLimitSyntax?)  │
│  Implementações: SQLite, MySQL, PostgreSQL, Oracle, SQLServer,     │
│                  Firebird (e “Generic” para FlatFiles)            │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│  Core: Lexer (opcional, fase 2) / Tokenização por dialeto         │
│        Parser: escape, binds, type detection usando o dialect     │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Componentes concretos sugeridos

1. **`GenericDatabase\Helpers\Parsers\SQL\Dialect\DialectInterface`**  
   - Métodos: `getIdentifierQuoteChar()`, `getBindStyle()` (named vs positional, caractere), `getReservedWords()`, `getCommentPatterns()`, opcionalmente `getLimitClausePattern()`.

2. **`GenericDatabase\Helpers\Parsers\SQL\Dialect\*Dialect`**  
   - Uma classe (ou config) por dialeto: `SQLiteDialect`, `MySQLDialect`, `PostgreSQLDialect`, `OracleDialect`, `SqlServerDialect`, `FirebirdDialect`, `GenericDialect` (FlatFiles).

3. **`GenericDatabase\Helpers\Parsers\SQL\SqlParser`** (nova facade ou evolução de `Parse`)  
   - Recebe `DialectInterface` (ou string/enum de dialeto) e delega:
     - `escape($sql, $dialect)`
     - `binding($sql, $dialect)`
     - `parseParameters($sql, $dialect)`
     - `detectType($sql, $dialect)` (delegando ao TypeDetector com regras do dialeto, se necessário)

4. **Lexicon**  
   - Pode virar “base” compartilhada; cada dialeto **estende** ou **sobrescreve** com palavras extras (ex.: MySQL `AUTO_INCREMENT`, Oracle `ROWNUM`).

5. **TypeDetector**  
   - Refatorar para receber **dialect** (ou padrões do dialect) para comentários e, em fase futura, para variações de sintaxe (ex.: CTE, LIMIT).

6. **Regex de cláusulas (SELECT, FROM, WHERE, etc.)**  
   - **Opção A (recomendada a curto prazo):** Um único conjunto de regex “genérico” em `Helpers\Parsers\SQL\ClausePatterns` (ou no Dialect genérico), usado por **uma única** classe Criteria/Clause compartilhada que recebe o dialect apenas para escape/binding ao montar a string final.  
   - **Opção B:** Cada dialeto pode expor pequenas variações de padrão (ex.: LIMIT vs FETCH vs TOP); o parser usa o dialect para escolher o padrão.  
   - Isso permite **unificar** as classes Criteria e Clause em um **Core** (ou por “família”: RDBMS vs FlatFiles), em vez de uma por engine.

### 4.3 Fluxo com queries puras (sem quebrar)

- `Connection::query($sql)` / `prepare($sql)` continuam recebendo a string.
- O **StatementsHandler** (ou ponto único de “prepare”) obtém o **dialeto** da connection (ex.: `$this->connection->getDialect()` ou pelo driver name).
- Chama `SqlParser::escape($sql, $dialect)` e `SqlParser::binding(...)` / `parseParameters(...)` como hoje, mas com dialeto vindo da strategy.
- O restante do fluxo (enviar ao driver, executar) permanece igual; **suporte a queries puras continua 100% funcional**.

---

## 5. Impacto da refatoração

### 5.1 Onde há impacto

| Área | Impacto | Ação sugerida |
|------|---------|----------------|
| **Helpers/Parsers/SQL/** | Alto | Introduzir `Dialect\*` e `SqlParser`; migrar `Parse` e `TypeDetector` para usar dialect; manter compatibilidade (por exemplo, `Parse::escape($s, Parse::SQL_DIALECT_*)` delega para `SqlParser::escape($s, dialectFromConstant)`). |
| **Lexicon** | Médio | Manter como base; dialetos podem acrescentar/sobrescrever listas. |
| **Engine/*/QueryBuilder/Regex.php** | Alto | **Unificar**: uma classe `GenericDatabase\Helpers\Parsers\SQL\ClauseRegex` (ou por família) implementando `IRegex`; engines passam a usar essa classe ou um dialect que fornece padrões. Remover duplicação entre SQLite/MySQLi/PgSQL/OCI/etc. |
| **Engine/*/QueryBuilder/Criteria.php** | Alto | **Unificar** em um Core (ex.: `Core\QueryBuilder\Criteria`) que usa `ClauseRegex` + dialect; engines só configuram dialect. |
| **Engine/*/QueryBuilder/Clause.php** | Alto | Idem: unificar onde a lógica for idêntica; variações apenas por dialect/config. |
| **Engine/*/QueryBuilder/Builder.php** | Médio | Trocar chamadas `Parse::escape(...)` por `SqlParser::escape(..., $this->dialect)` (ou connection’s dialect); reduzir duplicação entre FlatFiles extraindo base comum (trait ou classe abstrata). |
| **Engine/*/Connection/Statements/StatementsHandler.php** | Médio | Obter dialeto da connection e usar `SqlParser` em vez de `Parse` direto onde fizer sentido. |
| **Interfaces** | Baixo | Manter `IRegex`, `IBuilder`; eventualmente `ConnectionInterface::getDialect()`. |

### 5.2 Ordem sugerida de implementação (sem quebrar queries puras)

1. **Fase 1 – Dialetos e parser**
   - Criar `DialectInterface` e implementações (SQLite, MySQL, PostgreSQL, Oracle, SQL Server, Firebird, Generic).
   - Criar `SqlParser` que usa dialect para escape, binding, parseParameters.
   - Fazer `Parse` delegar para `SqlParser` com dialect derivado das constantes atuais (BACKTICK/DOUBLE_QUOTE/etc.) para **compatibilidade**.
   - Testes: garantir que queries puras e prepare continuem iguais.

2. **Fase 2 – TypeDetector e Info**
   - TypeDetector passar a aceitar dialect (opcional) para comentários e futuras variações.
   - Manter assinaturas atuais com default para não quebrar chamadas existentes.

3. **Fase 3 – Unificação de Regex e Criteria**
   - Extrair regex de cláusulas para `Helpers\Parsers\SQL` ou Core; uma implementação de `IRegex` por “família” (RDBMS vs FlatFiles) ou uma única com pequenas variações por dialect.
   - Unificar Criteria (e Clause) em Core; engines injetam dialect ou connection.

4. **Fase 4 – Builders e Connections**
   - Builders passam a usar `SqlParser` + dialect; reduzir duplicação entre FlatFiles (trait ou AbstractFlatFileBuilder).
   - Connections expõem `getDialect()` para StatementsHandler e QueryBuilder.

### 5.3 Riscos e mitigações

- **Regressão em escape/binding:** Mitigar com testes automatizados por dialeto (casos de escape, `:name`, `?`, `$1`) e testes de integração com queries puras.
- **Performance:** Strategy e facade adicionam uma indireção; impacto tende a ser irrelevante comparado a I/O de banco; manter escape/binding como código direto sem over-engineering.
- **Escopo:** Fazer em fases permite parar após Fase 1 ou 2 com ganho claro (parser unificado por dialeto) sem obrigar a unificar todo o QueryBuilder de uma vez.

---

## 6. Resumo: padrão e benefícios

- **Padrão recomendado:** **Strategy (por dialeto) + Dialect Profile + Facade (SqlParser)**. Abstract Factory opcional para criação por tipo de connection.
- **Benefícios:** Um único ponto para regras de escape, binding e (se desejar) padrões de cláusulas; suporte a SQLite, MySQL, Oracle, PostgreSQL, SQL Server e Firebird com configuração clara; expansão de QueryBuilders via novo dialeto em vez de copiar várias classes; código menos frágil e menos volátil.
- **Queries puras:** Continuam funcionando; a refatoração apenas alimenta o fluxo existente com um parser baseado em dialeto.
- **Impacto:** Alto em `Helpers/Parsers/SQL`, Regex e Criteria/Clause (unificação); médio em Builders e StatementsHandlers; baixo em interfaces. Implementação em fases reduz risco e permite entregas incrementais.

Este documento pode ser usado como base para um plano de ação detalhado (tarefas por fase) ou para discussão de arquitetura com a equipe.
