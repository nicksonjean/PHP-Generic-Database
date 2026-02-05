# Análise: Subqueries (FROM, WHERE IN, SELECT), Múltiplos UNION/EXISTS e Flat Files

Este documento esclarece o estado atual da implementação das cláusulas EXISTS, UNION/UNION ALL e subqueries, responde ao item **não atendido** pelo outro agente — *"Estender suporte a subqueries em FROM, WHERE IN, e SELECT"* — e descreve o que falta para suporte completo em Flat Files.

---

## 1. Checklist do outro agente vs estado atual

| Item | Status | Observação |
|------|--------|------------|
| union() e unionAll() em IQueryBuilder / IClause | ✅ | Implementado |
| QueryObject com union/unionAll em validProperties | ✅ | Implementado |
| Lógica UNION/UNION ALL nos builders SQL | ✅ | Implementado |
| Lógica UNION/UNION ALL para Flat Files | ✅ | Implementado (JSON, CSV, XML, YAML, INI, NEON) |
| whereExists() e whereNotExists() em IQueryBuilder | ✅ | Implementado |
| Suporte a EXISTS nos builders SQL | ✅ | Implementado |
| Suporte a EXISTS em Criteria (subqueries) | ✅ | Implementado |
| **Estender subqueries em FROM, WHERE IN e SELECT** | ❌ **Não atendido** | Detalhes na seção 2 |
| Exemplos de uso | ✅ | `tests/Engine/UnionExistsQueryBuilderTest.php` |

Conclusão: o único ponto do checklist que **não** foi atendido é o suporte a subqueries em **FROM**, **WHERE IN** e **SELECT**. O restante está implementado.

---

## 2. Múltiplos UNION, UNION ALL e múltiplos EXISTS

### 2.1 Múltiplos UNION e UNION ALL — ✅ Suportado

- `query->union` e `query->unionAll` são **arrays**; cada chamada a `union()` ou `unionAll()` adiciona um elemento.
- Nos builders (SQL e Flat Files), há iteração sobre esses arrays para:
  - montar o SQL (`appendUnionToParts` / equivalente),
  - mesclar placeholders em `getValues()`,
  - na execução Flat File: executar cada subquery e mesclar resultados com `mergeResults()`.

Exemplo válido:

```php
QueryBuilder::with($conn)
    ->select('name')->from('users')
    ->union(QueryBuilder::with($conn)->select('name')->from('employees'))
    ->union(QueryBuilder::with($conn)->select('title')->from('roles'))
    ->unionAll(QueryBuilder::with($conn)->select('label')->from('tags'))
    ->build();
```

### 2.2 Múltiplos EXISTS / NOT EXISTS — ✅ Suportado

- A cláusula WHERE é um **array**; cada chamada a `whereExists()` ou `whereNotExists()` adiciona um item com `type => Where::EXISTS()`, `subquery` e `negate`.
- Nos builders, todos os itens do tipo EXISTS são percorridos (ex.: `buildExistsWhereString()` no PDO e nos Flat Files) e concatenados com `AND`.

Exemplo válido:

```php
QueryBuilder::with($conn)
    ->select('*')->from('users u')
    ->whereExists(QueryBuilder::with($conn)->select('1')->from('orders o')->where('o.user_id', '=', 'u.id'))
    ->whereNotExists(QueryBuilder::with($conn)->select('1')->from('banned b')->where('b.user_id', '=', 'u.id'))
    ->build();
```

Resumo: **múltiplos UNION, múltiplos UNION ALL e múltiplos EXISTS na mesma query já estão atendidos** pelas últimas modificações.

---

## 3. Subqueries em FROM, WHERE IN e SELECT — não implementado

O ponto *"Estender suporte a subqueries em FROM, WHERE IN, e SELECT"* **não foi implementado** e continua em aberto.

### 3.1 FROM com subquery

- **Desejado:** `FROM (SELECT ...) AS alias` ou `from(IQueryBuilder $subquery, string $alias)`.
- **Atual:** `getFrom()` e regex tratam apenas nome de tabela (e alias). Não há tipo “subquery” em FROM nem uso de `IQueryBuilder` em `buildFrom()`.
- **O que falta:**
  - Estender Criteria/Clause para aceitar em FROM um `IQueryBuilder` (ou string SQL) e um alias.
  - Nos builders SQL: em `buildFrom()`, detectar “subquery” e gerar `( subquery->build() ) AS alias`.
  - Em Flat Files: definir semântica (ex.: resultado da subquery como “tabela” em memória) e implementar em `execute()`.

### 3.2 WHERE IN com subquery

- **Desejado:** `WHERE col IN (SELECT col FROM ...)` ou `whereIn('col', IQueryBuilder $subquery)`.
- **Atual:** WHERE IN aceita apenas lista de valores (ex.: `WHERE id IN (1,2,3)`). Criteria não prevê “IN (subquery)”.
- **O que falta:**
  - Nova forma em Criteria/Clause para IN com subquery (ex.: `aggregation.type = IN_SUBQUERY`, `subquery => IQueryBuilder`).
  - Nos builders SQL: gerar `col IN ( subquery->build() )` e mesclar `$subquery->getValues()` em `getValues()`.
  - Em Flat Files: executar a subquery, obter conjunto de valores e filtrar com esse conjunto (equivalente a IN).

### 3.3 SELECT com subquery escalar

- **Desejado:** `SELECT (SELECT ...) AS col` ou coluna do tipo “subquery”.
- **Atual:** SELECT trata colunas como metadado ou função; não há tipo “subquery” em coluna.
- **O que falta:**
  - Estender Criteria/Clause para coluna que seja `IQueryBuilder` (subquery escalar) com alias.
  - Nos builders SQL: gerar `( subquery->build() ) AS alias` no SELECT e mesclar placeholders.
  - Em Flat Files: para cada linha do resultado principal, executar a subquery (correlacionada ou não) e anexar o valor escalar à linha.

---

## 4. Flat Files: status real e o que “parcialmente compatível” significa

### 4.1 O que está implementado (todos os formatos: JSON, CSV, XML, YAML, INI, NEON)

| Funcionalidade    | Build (SQL string) | getValues() | Execute (resultado em memória)   |
|-------------------|------------------- |-------------|----------------------------------|
| UNION             | ✅                | ✅          | ✅ (concatenação + deduplicação) |
| UNION ALL         | ✅                | ✅          | ✅ (concatenação sem dedup)      |
| EXISTS/NOT EXISTS | ✅                | ✅          | ❌ (não filtra linhas)           |

- **UNION/UNION ALL:** Completos para Flat Files: string SQL, placeholders e execução (concatenação de resultados de subqueries QueryBuilder).
- **EXISTS/NOT EXISTS:** Implementados apenas no “framework”: entram na string SQL (ex.: `buildExistsWhereString()`) e nos placeholders. Na **execução** (`execute()`), as condições EXISTS **não** são aplicadas: `buildWhere()` ignora itens do tipo EXISTS e o `DataProcessor` não avalia “existe subquery retornando linhas”. Por isso o texto “**EXISTS: Framework implementado, execução limitada**” continua correto.

### 4.2 Subqueries em Flat Files

- **Atual:** Apenas subqueries via **QueryBuilder** (ex.: `union(IQueryBuilder)`, `whereExists(IQueryBuilder)`). SQL bruto em UNION/EXISTS **não** é interpretado nem executado.
- **Limitação:** Não há parser de SQL para Flat Files; portanto “subqueries” em Flat Files = apenas chamadas fluentes com `IQueryBuilder`.

---

## 5. O que falta para dar suporte completo a Flat Files

### 5.1 EXISTS com execução real (alta prioridade)

- **Objetivo:** Que `execute()` em Flat Files respeite WHERE EXISTS / NOT EXISTS.
- **Abordagem sugerida:**
  1. Em `executeSingle()` (ou equivalente), após obter o conjunto de linhas candidatas (como hoje), para cada item WHERE do tipo EXISTS:
     - Executar a subquery (QueryBuilder) sobre o mesmo (ou outro) dataset, com contexto da linha atual se for correlacionada.
  2. Filtrar: manter apenas linhas para as quais EXISTS retorna true (ou NOT EXISTS false).
- **Desafios:** Subqueries correlacionadas (ex.: `where('orders.user_id', '=', 'users.id')`) exigem passar a “linha atual” para o contexto da subquery; definir como o Connection/QueryBuilder expõe o dataset e o contexto de linha para Flat Files.

### 5.2 WHERE IN com subquery (média prioridade)

- **Objetivo:** `whereIn('col', IQueryBuilder $subquery)` em Flat Files.
- **Abordagem:** Executar a subquery uma vez, obter lista de valores da coluna; aplicar filtro IN sobre essa lista no `DataProcessor` (ou no Builder antes de chamar o processor). Depende de implementar primeiro WHERE IN (subquery) na API e nos builders (ver seção 3.2).

### 5.3 FROM com subquery (menor prioridade para Flat Files)

- **Objetivo:** `from(IQueryBuilder $subquery, string $alias)`.
- **Abordagem:** Executar a subquery, usar o resultado como “tabela” nomeada pelo alias (array de linhas keyed ou com alias), e aplicar o restante da query sobre esse resultado. Requer definir como JOIN e demais cláusulas enxergam “tabelas” em memória.

### 5.4 SQL bruto em UNION (opcional)

- **Objetivo:** Aceitar string SQL em `union('SELECT ...')` para Flat Files.
- **Abordagem:** Parser mínimo que identifique SELECT ... FROM e, a partir daí, simular execução sobre os dados (ou rejeitar com mensagem clara). Trabalhoso e de ganho limitado; manter “apenas QueryBuilder” é aceitável.

### 5.5 Resumo de prioridades para Flat Files

1. **EXISTS na execução** — filtrar linhas em `execute()` conforme EXISTS/NOT EXISTS (QueryBuilder).
2. **WHERE IN (subquery)** — após existir na API geral, implementar em Flat Files executando a subquery e aplicando IN.
3. **FROM (subquery)** — tratar resultado da subquery como tabela em memória.
4. **SQL bruto em UNION** — opcional; documentar como limitação conhecida.

---

## 6. Conclusão

- **Múltiplos UNION / UNION ALL e múltiplos EXISTS:** já suportados; não há pendência nas últimas modificações.
- **Subqueries em FROM, WHERE IN e SELECT:** **não** foram atendidos; é o único item do checklist do outro agente em aberto.
- **Flat Files:** “Parcialmente compatível” reflete bem o estado:
  - UNION/UNION ALL: completos (build, getValues, execute).
  - EXISTS: framework (build + getValues) sim; **execução** (filtrar por EXISTS) não.
  - Subqueries: apenas via QueryBuilder; SQL bruto não.

Este documento pode ser usado como base para uma nova análise ou para planejar as próximas tarefas (FROM/IN/SELECT + EXISTS na execução em Flat Files).
