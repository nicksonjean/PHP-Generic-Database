# Plano de Ação: Compatibilizar 100% das implementações DB e FlatFiles (Connection + QueryBuilder) para Subqueries, incluindo SQL puro via query/prepare

**Objetivo:** Garantir paridade entre DB e FlatFiles para subqueries (UNION, UNION ALL, EXISTS, NOT EXISTS e futuras FROM/WHERE IN/SELECT), tanto via **QueryBuilder** quanto via **SQL puro** (`Connection::query()` e `Connection::prepare()`), sem quebrar o que já funciona.

**Última atualização:** 2025-02-07

**Status de implementação:**
- **Interface IFlatFileConnection e getTableData:** Reprovação: não criar interface nem novo método nas classes principais Connection. Revertido.
- **Fase 1 (EXISTS na execução):** Lógica mantida nos Builders (applyExistsFilter, contextRow); execução principal volta a ser via Connection (query + fetchAll). EXISTS/UNION em SQL puro tratados no FetchHandler (Fase 2).
- **Fase 2 (UNION/UNION ALL + EXISTS no FetchHandler):** Concluída. Helper `FlatFileSelectParser` (split UNION/UNION ALL, extract/strip EXISTS) e integração em todos os FetchHandlers (JSON, CSV, INI, XML, NEON, YAML). SQL puro e SQL gerado pelo QueryBuilder passam pelo mesmo fluxo: UNION split → execução por segmento → merge (dedup para UNION) → ORDER BY/LIMIT do sufixo; WHERE com EXISTS → strip EXISTS do WHERE → WHERE normal → filtro por linha com subquery correlacionada.
- **Fases 3–4:** Pendentes (documentação/matriz).

---

## 1. Estado atual (resumo)

| Canal | UNION / UNION ALL | EXISTS / NOT EXISTS | Observação |
|------|-------------------|---------------------|------------|
| **DB – QueryBuilder** | ✅ Completo | ✅ Completo | build + getValues + execução nativa |
| **DB – query/prepare (SQL puro)** | ✅ Completo | ✅ Completo | Motor SQL executa direto |
| **FlatFiles – QueryBuilder** | ✅ Completo | ✅ Completo | build + getValues + execução via FetchHandler (UNION + EXISTS no parse) |
| **FlatFiles – query/prepare (SQL puro)** | ✅ Completo | ✅ Completo | FetchHandler: FlatFileSelectParser (split UNION, extract EXISTS) + execução por segmento e filtro EXISTS por linha |

Conclusão: após a Fase 2, FlatFiles suportam UNION, UNION ALL, EXISTS e NOT EXISTS tanto via QueryBuilder quanto via SQL puro (`query()` / `prepare()`), em todas as engines (JSON, CSV, INI, XML, NEON, YAML).

---

## 2. Escopo do plano

- **Incluído:**
  - Fazer com que `Connection::query()` e `Connection::prepare()` em FlatFiles interpretem e executem corretamente SQL contendo **UNION**, **UNION ALL**, **EXISTS** e **NOT EXISTS** (subqueries).
  - Completar a execução de **EXISTS/NOT EXISTS** no fluxo do QueryBuilder FlatFiles (DataProcessor / Builder), para filtrar linhas quando a subquery é `IQueryBuilder`.
  - Manter comportamento atual de todas as queries que já funcionam (SELECT simples, JOIN, WHERE, ORDER BY, LIMIT, DML, QueryBuilder UNION).

- **Fora do escopo (podem ser planos futuros):**
  - Subqueries em FROM, WHERE IN (subquery), SELECT escalar (subquery).
  - Parser de SQL bruto para FlatFiles além do necessário para UNION/EXISTS (ex.: CTEs, sintaxe completa).

---

## 3. Princípios para não quebrar o que já funciona

1. **Testes primeiro:** Garantir que testes existentes (ex.: `UnionExistsQueryBuilderTest`, amostras Fetch/FetchAll por engine) continuem passando antes e depois de cada fase.
2. **Feature flags / fallback:** Se necessário, suportar SQL com UNION/EXISTS apenas quando o parser identificar claramente o padrão; queries que não casem continuam no fluxo atual (SELECT simples).
3. **Uma engine de referência:** Implementar e estabilizar primeiro em **uma** engine Flat (ex.: JSON); depois replicar o mesmo padrão para CSV, XML, YAML, INI, NEON.
4. **Parser incremental:** Estender o parser de SQL (parseQueryComponents / extração de WHERE) para detectar UNION/EXISTS **sem** reescrever a lógica já existente de SELECT/FROM/WHERE/ORDER/LIMIT.

---

## 4. Fases do plano

### Fase 1 – EXISTS na execução do QueryBuilder (FlatFiles)

**Objetivo:** Quando o usuário usa `QueryBuilder::with($conn)->...->whereExists($subquery)->fetchAll()` (ou `whereNotExists`) em FlatFiles, o resultado deve ser filtrado: manter apenas linhas para as quais a subquery retorna ao menos uma linha (EXISTS) ou nenhuma (NOT EXISTS).

**O que já existe:**  
Build da string SQL e `getValues()` já incluem EXISTS; o `Builder::execute()` chama `executeSingle()` e aplica WHERE, mas os itens de tipo `Where::EXISTS()` são ignorados no filtro (não há avaliação da subquery por linha).

**Passos sugeridos:**

1. **Documentar contrato de execução EXISTS (FlatFiles)**  
   - Em `executeSingle()` (ou método equivalente no Builder da engine): após obter o conjunto de linhas candidatas (como hoje), para cada item em `query->where` com `type === Where::EXISTS()`:  
     - Se `subquery` for `IQueryBuilder`, executar `$subquery->execute()` (ou equivalente que devolva array de linhas) no contexto correto.  
     - Definir como subqueries correlacionadas recebem a “linha atual” (ex.: passar linha como contexto para o Criteria/DataProcessor da subquery, ou documentar que na v1 só subqueries não correlacionadas são suportadas).

2. **Implementar filtro EXISTS em uma engine (ex.: JSON)**  
   - No `Builder::executeSingle()` (ou no ponto onde o DataProcessor aplica WHERE):  
     - Antes de aplicar `buildWhere()` para condições normais, iterar `query->where` e para cada item EXISTS:  
       - Executar a subquery (IQueryBuilder), obter resultado.  
       - Para cada linha candidata: se EXISTS → manter se resultado não vazio; se NOT EXISTS → manter se resultado vazio.  
     - Reduzir o dataset às linhas que passam em todos os EXISTS/NOT EXISTS.

3. **Testes**  
   - Adicionar ou estender testes em `tests/Engine/UnionExistsQueryBuilderTest.php` (ou equivalente) para FlatFiles:  
     - Query com `whereExists(IQueryBuilder)` e `whereNotExists(IQueryBuilder)` e validar que o número de linhas e os dados batem com a expectativa.

4. **Replicar para as outras 5 engines Flat**  
   - CSV, XML, YAML, INI, NEON: mesmo contrato e lógica no Builder (possivelmente extrair para um trait ou classe compartilhada se a estrutura permitir).

**Arquivos tipicamente envolvidos:**  
- `src/Engine/JSON/QueryBuilder/Builder.php` (e equivalentes CSV, XML, YAML, INI, NEON)  
- `src/Generic/FlatFiles/DataProcessor.php` (se for necessário expor algum método para “aplicar EXISTS dado um callable/resultado”)  
- Testes em `tests/Engine/`

**Risco:** Baixo, desde que a mudança seja apenas “aplicar filtro adicional” sobre o resultado já existente de `executeSingle()`.

---

### Fase 2 – Parser de SQL puro: detectar UNION / UNION ALL (FlatFiles)

**Objetivo:** Quando o usuário chama `$conn->query('SELECT a FROM t1 UNION SELECT a FROM t2')` (ou `prepare()` + bind + exec), o FetchHandler deve interpretar a string, separar as partes UNION/UNION ALL, executar cada SELECT em memória e concatenar resultados (com ou sem deduplicação).

**Passos sugeridos:**

1. **Estender o parser usado no fetch (parseQueryComponents ou equivalente)**  
   - Antes de tratar como um único SELECT, verificar se a string contém ` UNION ` ou ` UNION ALL ` (com cuidado para não casar dentro de substrings entre parênteses, ex.: `WHERE x IN (1, 2)`).  
   - Algoritmo sugerido: percorrer a string e, no nível de parênteses 0, localizar os tokens `UNION` e `UNION ALL` que separam SELECTs de mesmo nível.  
   - Resultado: array de segmentos, cada um sendo uma string SELECT; e um array de tipos (`'union'` ou `'union_all'`).

2. **Executar cada segmento e mesclar**  
   - Para cada string SELECT obtida no passo anterior, chamar o mesmo fluxo já usado para um único SELECT (ex.: `parseAndExecuteQuery` ou método que receba string e retorne array de linhas).  
   - Aplicar a mesma lógica já existente no QueryBuilder FlatFiles para UNION (remover duplicatas) e UNION ALL (manter todas).  
   - Garantir que o número de colunas seja compatível entre os SELECTs (mesma quantidade; nomes podem diferir, usar primeira query como referência).

3. **Integrar no fluxo de fetch**  
   - Em `executeStoredQuery()` do FetchHandler: se o parser detectar múltiplos SELECTs unidos por UNION/UNION ALL, usar o novo caminho (parsear segmentos → executar cada um → mesclar); caso contrário, manter o fluxo atual de `parseAndExecuteQuery($processedQuery)` para um único SELECT.

4. **Testes**  
   - Testes com `$conn->query('SELECT ... UNION SELECT ...')->fetchAll()` e `prepare('...')->...->fetchAll()` para pelo menos uma engine Flat (ex.: JSON).  
   - Garantir que testes existentes de SELECT simples e QueryBuilder UNION continuem passando.

5. **Replicar para as outras engines Flat**  
   - O parser pode ser extraído para uma classe helper (ex.: em `Helpers/Parsers/SQL/` ou em `Abstract/`) e reutilizado por todos os FetchHandlers; ou duplicado em cada engine se a arquitetura for muito específica por engine.

**Arquivos tipicamente envolvidos:**  
- `src/Engine/JSON/Connection/Fetch/FetchHandler.php` (e equivalentes nas outras 5 engines), método `parseAndExecuteQuery` e o parser de componentes (ex.: `parseQueryComponents`).  
- Possível novo helper: `src/Helpers/Parsers/SQL/FlatFileSelectParser.php` ou similar para “split por UNION/UNION ALL” e retorno de segmentos.  
- Testes.

**Risco:** Médio. Parsing de UNION em SQL bruto exige cuidado com parênteses e com strings literais que contenham a palavra UNION. Testes robustos e casos edge (ex.: UNION dentro de string) evitam regressões.

---

### Fase 3 – Parser de SQL puro: detectar EXISTS / NOT EXISTS no WHERE (FlatFiles)

**Objetivo:** Quando o usuário chama `$conn->query('SELECT * FROM t1 WHERE EXISTS (SELECT 1 FROM t2 WHERE t2.id = t1.id)')` (ou via `prepare()`), o FetchHandler deve identificar EXISTS/NOT EXISTS no WHERE, extrair a subquery (como string), executá-la no contexto da linha atual (correlacionada) ou uma vez (não correlacionada), e filtrar as linhas de t1 de acordo com o resultado.

**Passos sugeridos:**

1. **Estender o parser de WHERE (parseWhereClauseWithLogic / parseOneWhereCondition ou equivalente)**  
   - Detectar padrões `EXISTS (SELECT ...)` e `NOT EXISTS (SELECT ...)` na string WHERE.  
   - Extrair a subquery (string entre parênteses após EXISTS/NOT EXISTS), respeitando parênteses aninhados.  
   - Representar como condição especial (ex.: `type => 'exists', 'subquery_sql' => '...', 'negate' => bool`) além das condições já existentes (=, IN, LIKE, etc.).

2. **Executar a subquery por linha (correlacionada) ou uma vez (não correlacionada)**  
   - Para cada linha candidata do SELECT externo:  
     - Se a subquery referencia colunas da tabela externa (ex.: `t2.id = t1.id`), substituir na string da subquery os qualificadores da tabela externa pelos valores da linha atual (ex.: `t1.id` → valor da linha), gerando uma string SELECT executável.  
     - Chamar o mesmo executor usado para um único SELECT (ex.: `parseAndExecuteQuery`) com essa string.  
     - EXISTS → manter linha se o resultado não for vazio; NOT EXISTS → manter linha se for vazio.  
   - Otimização possível: se a subquery não referencia tabela externa, executá-la uma vez e reutilizar o resultado para todas as linhas.

3. **Integrar no fluxo de parseAndExecuteQuery**  
   - Após obter as linhas candidatas (FROM + JOIN + condições WHERE simples), antes de aplicar GROUP BY / ORDER / LIMIT, aplicar o filtro EXISTS/NOT EXISTS usando a lógica acima.  
   - Manter o restante do pipeline (projection, order, limit) inalterado.

4. **Testes**  
   - Testes com `$conn->query('SELECT ... WHERE EXISTS (SELECT ...)')->fetchAll()` e NOT EXISTS, para uma engine (ex.: JSON).  
   - Casos: subquery correlacionada e não correlacionada.  
   - Garantir que WHERE sem EXISTS continua funcionando.

5. **Replicar para as outras engines Flat**  
   - Mesma lógica no parser e no executor; compartilhar helper de “extrair EXISTS da string” e “executar subquery string no contexto de uma linha” se possível.

**Arquivos tipicamente envolvidos:**  
- FetchHandler de cada engine Flat: `parseAndExecuteQuery`, `parseWhereClauseWithLogic`, `parseOneWhereCondition` (ou equivalente).  
- Possível helper para extração de EXISTS e substituição de correlacionados.  
- Testes.

**Risco:** Alto. Subqueries correlacionadas exigem substituição segura de identificadores por valores (escaping, tipos). Recomenda-se começar por EXISTS não correlacionado e depois adicionar suporte correlacionado com testes bem definidos.

---

### Fase 4 – Unificação e documentação

**Objetivo:** Garantir que Connection e QueryBuilder estejam alinhados em comportamento e documentação, e que a matriz de compatibilidade seja atualizada.

**Passos sugeridos:**

1. **Atualizar matriz de compatibilidade**  
   - No documento `readme/Analysis/comparison/CONNECTION_AND_QUERYBUILDER_UNIFIED_COMPARISON.md` (ou equivalente), deixar explícito:  
     - FlatFiles: UNION/UNION ALL e EXISTS/NOT EXISTS suportados tanto via QueryBuilder quanto via `query()`/`prepare()` (SQL puro), com ressalvas se houver (ex.: subquery correlacionada em SQL puro com limitações).

2. **Atualizar análise DQL FlatFiles**  
   - Em `readme/Analysis/dql/DQL_SUBQUERIES_AND_FLATFILES_ANALYSIS.md`:  
     - Marcar como suportado: EXISTS na execução (QueryBuilder), SQL puro com UNION e EXISTS (query/prepare).  
     - Documentar limitações conhecidas (ex.: FROM subquery, IN (subquery), SELECT escalar ainda não suportados em SQL puro).

3. **Revisar amostras e exemplos**  
   - Verificar se `samples/` (JSON, CSV, XML, etc.) têm exemplos de Fetch/FetchAll com SQL contendo UNION ou EXISTS, e adicionar se fizer sentido.  
   - Manter `examples/union_exists_demo.php` (ou equivalente) atualizado para mostrar uso via Connection::query e via QueryBuilder.

4. **Checklist final**  
   - [ ] QueryBuilder FlatFiles: UNION, UNION ALL, whereExists, whereNotExists — build e execução.  
   - [ ] Connection FlatFiles query(): SELECT simples, SELECT com UNION, SELECT com EXISTS.  
   - [ ] Connection FlatFiles prepare(): mesmo casos.  
   - [ ] Testes automatizados cobrindo os casos acima.  
   - [ ] Nenhuma regressão em testes existentes.

---

## 5. Ordem recomendada e dependências

| Ordem | Fase | Dependência | Motivo |
|-------|------|-------------|--------|
| 1 | Fase 1 – EXISTS na execução (QueryBuilder) | Nenhuma | Fecha a lacuna “EXISTS só no build” no canal já usado (QueryBuilder). |
| 2 | Fase 2 – SQL puro UNION | Nenhuma | Independe de EXISTS; reutiliza lógica de merge já existente no QueryBuilder. |
| 3 | Fase 3 – SQL puro EXISTS | Fase 2 opcional | Pode reutilizar o mesmo executor de SELECT (parseAndExecuteQuery) para a subquery. |
| 4 | Fase 4 – Unificação e doc | Fases 1–3 | Só após comportamento estável. |

---

## 6. Riscos e mitigações

| Risco | Mitigação |
|-------|------------|
| Parser de SQL quebra queries atuais | Manter caminho antigo para “um único SELECT sem UNION/EXISTS”; novo caminho só quando padrão for detectado. Testes de regressão para SELECT simples, WHERE, JOIN, LIMIT. |
| Duplicação de lógica entre 6 engines | Extrair “split UNION”, “extrair EXISTS”, “executar subquery string” para helpers ou Abstract; cada FetchHandler apenas orquestra. |
| EXISTS correlacionado em SQL puro incorreto | Fase 3 pode ser entregue primeiro só para EXISTS não correlacionado; correlacionado em fase posterior com testes específicos. |
| Performance (muitas execuções de subquery) | Documentar; otimizar depois (cache por subquery não correlacionada, etc.). |

---

## 7. Resumo executivo

- **Objetivo:** Compatibilidade 100% DB vs FlatFiles para subqueries (UNION, UNION ALL, EXISTS, NOT EXISTS) via **QueryBuilder** e via **SQL puro** (`query`/`prepare`).  
- **Estado atual:** QueryBuilder FlatFiles tem UNION completo e EXISTS só no build; SQL puro em FlatFiles não trata UNION nem EXISTS.  
- **Fases:** (1) EXISTS na execução do QueryBuilder FlatFiles; (2) Parser e execução de SQL puro com UNION/UNION ALL; (3) Parser e execução de SQL puro com EXISTS/NOT EXISTS no WHERE; (4) Unificação e documentação.  
- **Princípio:** Implementar com testes, uma engine de referência primeiro, e fallback para não quebrar SELECT simples e fluxos atuais.

Este plano pode ser usado como guia para implementação incremental e para revisão de código (PRs) alinhados ao objetivo de paridade sem regressões.
