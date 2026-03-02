# Análise: Integração do php-sql-parser (SqlQueryAnalyzer) no PHP-Generic-Database

**Objetivo:** Avaliar a utilidade e os benefícios de integrar a biblioteca `php-sql-parser` (classe `SqlQueryAnalyzer`) no projeto PHP-Generic-Database, especificamente nos métodos `query()` e `prepare()` da classe `Connection` e no `QueryBuilder`.

**Última atualização:** 2025-02-16

---

## 1. Resumo Executivo

| Aspecto | Conclusão |
|---------|-----------|
| **Utilidade** | **Sim** — há benefícios concretos em cenários específicos |
| **Recomendação** | **Integração híbrida e opcional** — usar como camada complementar, não substituta |
| **Prioridade** | Média — benefícios incrementais; performance é fator limitante |

A biblioteca `php-sql-parser` oferece **validação e análise baseada em AST** (gramática SQL), superando as limitações do parser atual baseado em regex. Porém, o custo de performance em queries complexas exige uma abordagem **seletiva e configurável**.

---

## 2. Visão Geral da Biblioteca php-sql-parser

### 2.1 Arquitetura

```
php-sql-parser/
├── lexic/
│   ├── sql_lexic.g4      # Gramática SQL (ANTLR-like, Hoa Compiler)
│   └── sql_light.g4      # Gramática simplificada
├── src/
│   ├── SqlQueryAnalyzer.php   # Classe principal de análise
│   └── ParserSQL.php          # Script de demonstração
├── vendor/
│   └── hoa/compiler/          # Hoa Compiler (parser LL(k))
└── composer.json
```

### 2.2 Dependências

| Dependência | Versão | Propósito |
|-------------|--------|-----------|
| `hiqdev/hoa-compiler` | ^1.0 | Compilador LL(k) para gramática G4 |
| `marcocesarato/sqlparser` | ^0.2.106 | Parser SQL alternativo (se usado) |

**Nota:** O projeto usa `Hoa\Compiler\Llk` para carregar e executar a gramática `sql_lexic.g4`. A gramática define tokens e regras de produção para SQL padrão.

### 2.3 Fluxo de Parsing

```
SQL String → Hoa Compiler (grammar) → AST (TreeNode)
                                          ↓
                              SqlQueryAnalyzer($ast)
                                          ↓
                              Estrutura analisada + métodos de extração
```

1. **Carregamento da gramática:** `Hoa\Compiler\Llk::load(new Hoa\File\Read(GRAMMAR_FILE))`
2. **Parse:** `$compiler->parse($query, 'SelectQuery')` — retorna `TreeNode` (AST)
3. **Análise:** `new SqlQueryAnalyzer($ast)` — percorre a árvore e extrai componentes

---

## 3. Capacidades do SqlQueryAnalyzer

### 3.1 Extração de Componentes

| Método | Retorno | Uso Potencial |
|--------|---------|----------------|
| `getResult()` | Estrutura completa (type, queries, setOperations, orderBy, limit) | Validação, análise de tipo |
| `getQueries()` | Array de queries (principal + UNION) | Detecção de UNION/INTERSECT/EXCEPT |
| `getAllTables()` | Tabelas com alias, schema, subquery | Auditoria, otimização |
| `getAllColumns()` | Colunas com expression, alias, type | Validação de projeção |
| `getLiterals()` | Strings e números | Extração de valores fixos |
| `getParameters()` | Placeholders (?, :name, $1, @, #) | **Binding correto** |
| `getOrderedValues($includeLiterals, $simplified)` | Valores em ordem de aparição | **Prepare/bind** |
| `getSubqueries()` | Subqueries encontradas | Análise de complexidade |
| `getExistsExpressions()` | Expressões EXISTS | Suporte a EXISTS |
| `hasUnion()`, `hasSubqueries()`, `hasExists()`, `hasJoins()` | Booleanos | Detecção rápida |

### 3.2 Reconstrução de Query

| Método | Descrição |
|--------|-----------|
| `toSql()` | Reconstrui a query original a partir do AST |
| `toSqlWithEscape($escapeChar)` | Reconstrui com escape de identificadores customizado (`, ", [) |
| `toSqlWithValues($values)` | Substitui parâmetros por valores |

### 3.3 Suporte a Placeholders

O `SqlQueryAnalyzer` reconhece nativamente (via gramática):

- `?` — posicional (question_param)
- `:name` — nomeado (named_param)
- `$1`, `$2` — numerado PostgreSQL (numbered_param)
- `@var` — variável (at_param)
- `#name` — hash (hash_param)

`getOrderedValues()` retorna a ordem exata para binding, superando regex que pode falhar em subqueries aninhadas.

### 3.4 Tipos de Query Suportados pela Gramática

Conforme `sql_lexic.g4`:

- **SelectQuery** — SELECT, WITH (CTE), UNION/INTERSECT/EXCEPT, subqueries
- **InsertQuery** — INSERT
- **UpdateQuery** — UPDATE
- **DeleteQuery** — DELETE

O `SqlQueryAnalyzer` atual está focado em **SelectQuery** (conforme `ParserSQL.php` que usa `$compiler->parse($query, 'SelectQuery')`). INSERT/UPDATE/DELETE exigiriam extensão do analyzer.

---

## 4. Estado Atual do Projeto PHP-Generic-Database

### 4.1 Parser Atual: `Helpers\Parsers\SQL\Parse`

| Funcionalidade | Implementação | Limitação |
|----------------|---------------|-----------|
| Escape de identificadores | `Parse::escape($input, $dialect)` | Regex + palavras reservadas; falhas em subqueries complexas |
| Extração de parâmetros | `Parse::parseParameters($query, $dialect)` | Regex `/(:\w+)/` e `/\?/`; não considera contexto (literais vs identificadores) |
| Literais | `extractLiteralValues()` | Regex para strings e números; pode confundir em edge cases |
| Binding | `Parse::binding($input, $bindType)` | Substituição de `:name` por `?` ou `$n` |

**Problemas conhecidos (documentados em `SQL_PARSER_REFACTORING_AND_DIALECT_ARCHITECTURE.md`):**

- Fragmentação: Lexicon, Parse, TypeDetector, Regex por engine
- Regex frágil para subqueries, EXISTS, parênteses aninhados
- Dialeto como constante numérica; sem perfil unificado

### 4.2 Fluxo Connection → query() / prepare()

```
Connection::query($sql)
    → getStrategy()->query($sql)
        → StatementsHandler::query($params)
            → prepareStatement($params)
                → parse($params)  → Parse::escape($sql, $dialect)
                → $connection->prepare($parsedSql)
            → setQueryParameters(Parse::parseParameters($queryString, $dialect))
            → exec($statement)
```

```
Connection::prepare($sql, $params)
    → prepareStatement($sql)
    → Statement::bind([$statement, ...$params])
```

O `parse()` usa `Parse::escape()` para normalizar identificadores. O `parseParameters()` é usado para metadata (query rows, etc.), não para binding em si — o binding usa `Statement::bind()` com os argumentos passados pelo usuário.

### 4.3 QueryBuilder

- Cada engine (MySQLi, OCI, PgSQL, SQLite, Firebird, SQLSrv, PDO, ODBC) tem:
  - `Builder::parse()` — monta SQL e chama `Parse::escape()` em trechos
  - `Criteria` — extrai cláusulas via Regex
  - `Clause` — monta cláusulas
- Duplicação massiva entre engines (cf. análise de refatoração existente).

---

## 5. Benefícios da Integração

### 5.1 Para Connection::query() e Connection::prepare()

| Benefício | Descrição |
|-----------|-----------|
| **Validação prévia** | Verificar se a query é sintaticamente válida antes de enviar ao driver. Falha rápida com mensagem de erro da gramática. |
| **parseParameters mais robusto** | `getOrderedValues()` retorna a ordem exata de parâmetros considerando subqueries, EXISTS, etc. Útil para validar que o número de argumentos em `prepare($sql, $params)` corresponde aos placeholders. |
| **Normalização de escape** | `toSqlWithEscape($char)` pode converter identificadores entre dialetos (ex.: `"nome"` → `` `nome` `` para MySQL). |
| **Detecção de tipo** | `getResult()['type']` (SELECT, INSERT, etc.) pode complementar ou substituir `TypeDetector` em cenários onde o parser falha. |

### 5.2 Para QueryBuilder

| Benefício | Descrição |
|-----------|-----------|
| **Validação da saída** | Após `Builder::parse()` gerar o SQL, validar com o parser AST antes de executar. |
| **Unificação de Criteria** | Em vez de regex por engine, `SqlQueryAnalyzer` poderia extrair tabelas, colunas, WHERE, etc. de uma query raw — útil para **queries puras** passadas ao QueryBuilder ou para análise. |
| **Emitir SQL por dialeto** | `toSqlWithEscape()` permite gerar a mesma query com escape de identificadores adequado a cada engine. |

### 5.3 Casos de Uso Concretos

1. **Debug e auditoria:** Em modo de desenvolvimento, analisar queries antes da execução e logar: tipo, tabelas, colunas, parâmetros.
2. **Prepare com validação:** Antes de `prepare()`, validar a query e verificar que o número de placeholders corresponde aos argumentos.
3. **Migração de dialeto:** Converter query de uma sintaxe (ex.: Oracle) para outra (ex.: PostgreSQL) usando `toSqlWithEscape()` e eventualmente regras de LIMIT/OFFSET (se a gramática suportar).
4. **QueryBuilder híbrido:** Aceitar query raw e, se válida, usar o analyzer para extrair componentes e preencher o QueryObject (reduzindo necessidade de regex).

---

## 6. Desvantagens e Riscos

### 6.1 Performance

| Aspecto | Impacto |
|---------|---------|
| **Carregamento da gramática** | O custo de `load()` é significativo; deve ser feito uma vez e cacheado. |
| **Parse** | Em queries muito complexas (UNION, múltiplas subqueries, EXISTS aninhados), o usuário relatou que **chega a ser extremamente lento**. |
| **Custo por operação** | Cada `query()` ou `prepare()` que usar o parser adiciona latência. |

**Mitigação:** Usar o parser apenas quando configurado (ex.: `ATTR_VALIDATE_SQL` ou `ATTR_SQL_ANALYZER`) ou em modo debug. Não aplicar em produção por padrão para todas as queries.

### 6.2 Cobertura da Gramática

| Limitação | Detalhe |
|-----------|---------|
| **Dialetos** | A gramática é genérica. Sintaxes específicas (ex.: `FETCH FIRST n ROWS ONLY` vs `LIMIT n` vs `TOP (n)` vs `ROWNUM`) podem exigir regras adicionais. |
| **INSERT/UPDATE/DELETE** | O analyzer atual é focado em SelectQuery. Outros tipos precisam de extensão. |
| **Extensões proprietárias** | Funções específicas de Oracle, SQL Server, etc. podem não estar na gramática. |

### 6.3 Dependências e Manutenção

- **Hoa Compiler:** Biblioteca externa; patches aplicados no projeto (cf. `apply-patches.py`) para compatibilidade.
- **Gramática:** Manutenção da `sql_lexic.g4` para adicionar novos tokens ou regras.
- **Namespace:** O projeto usa `PhpSqlParser\SqlQueryAnalyzer`; o composer.json referencia `kphoen/sql-parser` — pode haver divergência de versões.

---

## 7. Proposta de Integração

### 7.1 Abordagem: Híbrida e Opcional

```
┌─────────────────────────────────────────────────────────────────┐
│  Connection::query() / prepare()                                  │
└─────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │  ATTR_VALIDATE_SQL / ATTR_SQL_ANALYZER?       │
                    └───────────────┬───────────────┘
                                    │
              ┌─────────────────────┼─────────────────────┐
              │ NÃO                  │ SIM                  │
              ▼                     ▼                     │
    Parse::escape()          Parse::escape()              │
    Parse::parseParameters() │ SqlQueryAnalyzer::parse()  │
              │              │ (se falhar → fallback)     │
              │              │ getOrderedValues() para   │
              │              │ validar params            │
              └──────────────────────┴─────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│  Driver (PDO, MySQLi, etc.)                                      │
└─────────────────────────────────────────────────────────────────┘
```

### 7.2 Pontos de Integração Sugeridos

| Local | Integração |
|-------|------------|
| **Options/Connection** | Nova opção `ATTR_VALIDATE_SQL` ou `ATTR_SQL_ANALYZER` (bool). Se true, usar parser antes de query/prepare. |
| **StatementsHandler::parse()** | Manter `Parse::escape()` como padrão. Opcionalmente, se `ATTR_SQL_ANALYZER` e query for SELECT, chamar parser para validação e usar `getOrderedValues()` para validar argumentos. |
| **StatementsHandler::query()** | Se validação ativa e parser disponível, validar antes de `prepareStatement`. Em caso de falha de parse, logar e continuar com fluxo atual (não bloquear). |
| **Helpers\Parsers\SQL** | Novo facade `SqlParserFacade` que encapsula: (a) Parse (regex) e (b) SqlQueryAnalyzer (AST). Escolhe com base em configuração e tipo de query. |
| **QueryBuilder** | Opcional: após `Builder::parse()` gerar SQL, se `ATTR_VALIDATE_SQL`, validar com parser. Útil em desenvolvimento. |

### 7.3 Exemplo de Uso (Configuração)

```php
// Opção 1: Validação em desenvolvimento
$connection->setAttribute(Connection::ATTR_VALIDATE_SQL, true);

// Opção 2: Apenas para prepare
$connection->setAttribute(Connection::ATTR_VALIDATE_SQL, 'prepare');

// Query e prepare continuam iguais
$connection->query('SELECT * FROM users WHERE id = ?');
$connection->prepare('SELECT * FROM users WHERE id = :id', [':id' => 1]);
```

### 7.4 Fallback e Performance

- Se o parser falhar (timeout, exceção, query não suportada), **fallback automático** para `Parse::escape()` e `Parse::parseParameters()`.
- Considerar **timeout** para o parse (ex.: 100ms); se exceder, usar fallback.
- **Cache** do compilador de gramática em singleton para evitar recarregar a cada request.

---

## 8. Comparação: Parse (Regex) vs SqlQueryAnalyzer (AST)

| Critério | Parse (Regex) | SqlQueryAnalyzer (AST) |
|----------|---------------|-------------------------|
| **Velocidade** | Rápido | Lento em queries complexas |
| **Precisão** | Pode falhar em subqueries aninhadas, EXISTS | Alta — gramática define estrutura |
| **Parâmetros** | Regex para `:name` e `?` | Ordem exata via AST |
| **Placeholders** | `:name`, `?` | `:name`, `?`, `$1`, `@`, `#` |
| **Escape** | Por dialect (backtick, double quote) | `toSqlWithEscape($char)` |
| **Reconstrução** | Não | `toSql()`, `toSqlWithValues()` |
| **Manutenção** | Regex frágil | Gramática mais estruturada |
| **Dependências** | Nenhuma | Hoa Compiler, gramática |

---

## 9. Conclusão e Recomendações

### 9.1 Conclusão

Há **utilidade e benefício** em utilizar o `SqlQueryAnalyzer` no projeto, **desde que**:

1. A integração seja **opcional** e **configurável**.
2. Haja **fallback** para o parser atual em caso de falha ou timeout.
3. O uso em produção seja **limitado** (ex.: apenas em modo debug ou para validação explícita).

### 9.2 Recomendações

| Prioridade | Ação |
|------------|------|
| **Alta** | Implementar `ATTR_VALIDATE_SQL` ou similar como opção de conexão. |
| **Alta** | Criar facade `SqlParserFacade` que escolhe entre Parse e SqlQueryAnalyzer conforme configuração. |
| **Média** | Integrar validação opcional em `prepare()` para verificar correspondência de parâmetros. |
| **Média** | Documentar o uso do parser em modo debug/auditoria. |
| **Baixa** | Avaliar uso no QueryBuilder para validação da saída (após build). |
| **Baixa** | Estender o analyzer para INSERT/UPDATE/DELETE se necessário. |

### 9.3 Próximos Passos

1. **Fase 1:** Adicionar opção de conexão e facade; integrar em `parse()` com fallback.
2. **Fase 2:** Testes de performance com queries de diferentes complexidades.
3. **Fase 3:** Ajustar timeout e cache do compilador conforme resultados.
4. **Fase 4:** Avaliar extensão para INSERT/UPDATE/DELETE se houver demanda.

---

## 10. Diagrama de Arquitetura Proposta

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         PHP-Generic-Database                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│  Connection::query($sql)  │  Connection::prepare($sql, $params)              │
└─────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  StatementsHandler::query() / prepare()                                        │
│  - prepareStatement() → parse()                                                │
└─────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  SqlParserFacade (novo)                                                       │
│  - escape($sql, $dialect)                                                     │
│  - parseParameters($sql, $dialect)                                           │
│  - validate($sql) [opcional]                                                  │
└─────────────────────────────────────────────────────────────────────────────┘
                    │                                    │
        ┌───────────┴───────────┐            ┌───────────┴───────────┐
        │ ATTR_VALIDATE_SQL=0   │            │ ATTR_VALIDATE_SQL=1    │
        ▼                       │            ▼                       │
┌───────────────────┐           │    ┌───────────────────┐           │
│ Parse (regex)      │           │    │ Hoa Compiler       │           │
│ - escape()         │           │    │ + SqlQueryAnalyzer  │           │
│ - parseParameters()│           │    │ - getOrderedValues()│          │
└───────────────────┘           │    │ - toSqlWithEscape() │           │
        │                       │    └───────────────────┘           │
        │                       │            │                       │
        │                       │            │ (timeout/falha?)      │
        │                       │            └───────────────────────┘
        └───────────────────────┴───────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Driver (PDO, MySQLi, OCI, etc.)                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 11. Benefícios Detalhados por Componente

### 11.1 Connection::query()

| Cenário | Benefício |
|---------|-----------|
| Query simples | Nenhum impacto se validação desativada. |
| Query com subquery/EXISTS/UNION | Com validação: detecta erros de sintaxe antes de enviar ao banco. |
| Debug | Log da estrutura (tabelas, colunas, parâmetros) para auditoria. |

### 11.2 Connection::prepare()

| Cenário | Benefício |
|---------|-----------|
| Parâmetros nomeados | `getOrderedValues()` garante ordem correta para binding; evita desalinhamento em subqueries. |
| Validação de argumentos | Comparar `count($params)` com número de placeholders extraídos pelo AST. |
| Placeholders mistos | Suporte a `?`, `:name`, `$1` na mesma query (se a gramática permitir). |

### 11.3 QueryBuilder

| Cenário | Benefício |
|---------|-----------|
| Build de query | Após `Builder::parse()`, validar SQL gerado com parser (modo dev). |
| Query raw como input | Se o QueryBuilder aceitar string, o analyzer pode extrair componentes e popular QueryObject. |
| Dialeto | `toSqlWithEscape()` para emitir SQL com escape correto por engine. |

---

## 12. Referências

- `php-sql-parser/src/SqlQueryAnalyzer.php` — Implementação do analyzer
- `php-sql-parser/src/ParserSQL.php` — Exemplo de uso
- `php-sql-parser/lexic/sql_lexic.g4` — Gramática SQL
- `src/Helpers/Parsers/SQL/Parse.php` — Parser atual
- `readme/Analysis/SQL_PARSER_REFACTORING_AND_DIALECT_ARCHITECTURE.md` — Arquitetura de parser e dialetos
- `readme/Analysis/dql/DQL_EXISTS_UNION_SUBQUERY_IMPACT_ANALYSIS.md` — Impacto de EXISTS/UNION
