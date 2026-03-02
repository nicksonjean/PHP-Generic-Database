# Firebird UNION — Resolução de ORDER BY por Ordinal

## Problema

O Firebird **não permite** referenciar nome de coluna no `ORDER BY` quando a query contém `UNION` ou `UNION ALL` diretos. Apenas ordinais posicionais são aceitos:

```sql
-- Inválido no Firebird com UNION
SELECT nome, idade FROM pessoas
UNION
SELECT nome, idade FROM funcionarios
ORDER BY nome          -- ❌ erro de sintaxe

-- Válido
ORDER BY 1             -- ✓ resolve para 'nome'
```

O desafio é permitir que o usuário escreva `->orderBy('nome ASC')` e o QueryBuilder traduza internamente para `ORDER BY 1` apenas quando estiver em contexto UNION/UNION ALL — sem expor esse detalhe ao consumidor da API.

---

## Abordagem Híbrida Proposta

Duas estratégias de resolução combinadas, ativadas apenas quando há UNION/UNION ALL:

| Contexto do SELECT | Estratégia | Fonte dos ordinals |
|---|---|---|
| Colunas explícitas (`SELECT nome, idade`) | **Resolução estática** | Parse do `$query->select['columns']` — sem I/O |
| Wildcard (`SELECT *`) | **Resolução via DDL** | Query a `RDB$RELATION_FIELDS` da primeira tabela do FROM |

A resolução estática tem precedência. A DDL lookup só ocorre como fallback quando `*` é detectado.

---

## Viabilidade

### Resolução Estática (SELECT com colunas explícitas)

**Viável sem restrições.** Os dados já estão disponíveis em `$this->query->select['columns']` no momento do `buildOrder()`. O mapeamento é:

```
$this->query->select['columns'][0] → ordinal 1
$this->query->select['columns'][1] → ordinal 2
...
```

Cada item da estrutura contém `column`, `alias` e `prefix`. A resolução compara o nome recebido no `ORDER BY` contra `column` e `alias` de cada item. O primeiro match define o ordinal.

**Casos cobertos:**

| SELECT | ORDER BY entrada | Resolve para |
|---|---|---|
| `nome, idade` | `nome` | `1` |
| `nome AS n, idade` | `n` *(alias)* | `1` |
| `t.nome, t.idade` | `nome` | `1` |
| `nome, idade` | `3` *(já ordinal)* | `3` *(passa direto)* |

**Caso não coberto:** colunas com função sem alias (`COUNT(id)`) — não há chave de texto simples para mapear. Comportamento: passa o valor original e deixa o Firebird rejeitar com erro nativo.

---

### Resolução via DDL (SELECT \*)

**Viável com pré-condições.** Requer:

1. **Conexão ativa** disponível no momento do build — o `Builder` precisa receber opcionalmente uma referência de conexão.
2. **Tabela resolvível** — a primeira tabela do `$this->query->from` é usada como referência para a query DDL.

**Query DDL ao Firebird:**

```sql
SELECT TRIM(RDB$FIELD_NAME) AS column_name,
       RDB$FIELD_POSITION   AS field_position
FROM   RDB$RELATION_FIELDS
WHERE  RDB$RELATION_NAME = UPPER('nome_da_tabela')
ORDER  BY RDB$FIELD_POSITION
```

`RDB$FIELD_POSITION` é **0-based**, portanto `ordinal = field_position + 1`.

O resultado é cacheado em memória (array estático por `tableName`) para evitar múltiplas queries DDL para a mesma tabela dentro do mesmo request.

**Limitações desta estratégia:**

| Cenário | Comportamento |
|---|---|
| UNION com tabelas de schemas diferentes | Usa sempre a primeira tabela do FROM do primeiro SELECT como referência |
| Alias de tabela (`FROM pessoas AS p`) | Extrai o nome real `pessoas`, ignora o alias |
| Subquery no FROM | Não aplicável — não há tabela DDL real para consultar; lança exceção informativa |
| Firebird sem permissão a `RDB$RELATION_FIELDS` | Lança exceção com mensagem clara |

---

## Problemas Estruturais e Resoluções

### 1. Conexão em Build Time

**Problema:** O `Builder` atual é stateless e independente de conexão. Injetar conexão diretamente viola a separação de responsabilidades.

**Resolução:** Introduzir uma interface opcional `ISchemaResolver` injetável via setter:

```php
interface ISchemaResolver
{
    public function getColumnOrdinals(string $tableName): array; // ['nome' => 1, 'idade' => 2]
}
```

O `Builder` recebe `?ISchemaResolver $schemaResolver = null`. Quando nulo e SELECT é `*` em contexto UNION, lança `Exceptions` informativa orientando o usuário a usar ordinal diretamente.

Dessa forma, o `Builder` permanece utilizável sem conexão para o caso de colunas explícitas — o acoplamento de conexão é opt-in.

---

### 2. Round-Trip Extra por Query

**Problema:** Cada query principal com `SELECT *` + UNION dispararia uma query DDL adicional.

**Resolução:** Cache estático por nome de tabela dentro do mesmo processo:

```php
private static array $ordinalCache = []; // ['PESSOAS' => ['nome' => 1, 'idade' => 2]]
```

Na prática: uma query DDL por tabela única por request PHP. Em ambientes com OPcache/long-running (Swoole, RoadRunner), o cache persiste entre requests — comportamento correto, pois DDL raramente muda em runtime.

---

### 3. UNION com Tabelas de Schemas Diferentes

**Problema:** `SELECT * FROM t1 UNION SELECT * FROM t2` — qual tabela define os ordinals?

**Resolução:** O SQL padrão define que os ordinals no `ORDER BY` referem-se sempre à **posição no SELECT list do primeiro SELECT**. Portanto, a primeira tabela do primeiro FROM é a fonte canônica — comportamento correto e alinhado ao padrão SQL.

Se as tabelas tiverem schemas incompatíveis, o próprio Firebird já rejeita o UNION antes do ORDER BY — não é responsabilidade do Builder validar isso.

---

### 4. Aliases no SELECT com DDL

**Problema:** `SELECT nome AS n FROM pessoas UNION ...` — o DDL retorna `nome`, mas o result set expõe `n`. Se o usuário usa `->orderBy('n')`, a DDL lookup não encontraria `n`.

**Resolução:** A estratégia híbrida resolve isso naturalmente: quando há colunas explícitas (mesmo com `AS`), a **resolução estática** é usada, não a DDL. A DDL só é acionada para `SELECT *`. Nesse caso, não há aliases porque `*` não define aliases — o result set usa os nomes reais das colunas, que são exatamente o que a DDL retorna.

---

### 5. Ordinal Já Informado pelo Usuário

**Problema:** Se o usuário já passa `->orderBy('2 ASC')`, a resolução não deve tentar converter.

**Resolução:** Antes de qualquer resolução, verificar se o valor do ORDER BY é numérico puro (`is_numeric($column)`). Se sim, passa direto sem lookup.

---

## Plano de Ação

### Fase 1 — Resolução Estática (sem dependência de conexão)

**Escopo:** `Builder::buildOrder()` + método auxiliar privado.

**Arquivos afetados:** `src/Engine/Firebird/QueryBuilder/Builder.php`

**Passos:**

1. Em `buildOrder()`, detectar contexto UNION:
   ```php
   $isUnionContext = !empty($this->query->union) || !empty($this->query->unionAll);
   ```

2. Criar método privado `resolveOrderOrdinal(string $columnName): string`:
   - Se `is_numeric($columnName)` → retorna `$columnName` (já é ordinal)
   - Itera `$this->query->select['columns']` com índice
   - Compara contra `column` e `alias` de cada item (case-insensitive)
   - Match → retorna `(string)($index + 1)`
   - Sem match → retorna `$columnName` original (Firebird emitirá erro nativo)

3. Em `buildOrder()`, aplicar `resolveOrderOrdinal()` somente quando `$isUnionContext === true`.

4. Detectar `SELECT *`:
   - Se há exatamente um item em `select['columns']` com `column === '*'` → lançar `Exceptions` com mensagem:
     ```
     "ORDER BY por nome de coluna em UNION com SELECT * requer resolução via schema.
      Use ordinal diretamente (ex: orderBy('1 ASC')) ou injete um ISchemaResolver."
     ```

**Resultado esperado:** Queries com colunas explícitas + UNION funcionam automaticamente. Queries com `SELECT *` recebem exceção clara.

---

### Fase 2 — Interface ISchemaResolver

**Escopo:** Nova interface + implementação Firebird + injeção no Builder.

**Arquivos novos:**
- `src/Interfaces/QueryBuilder/ISchemaResolver.php`
- `src/Engine/Firebird/QueryBuilder/FirebirdSchemaResolver.php`

**Passos:**

1. Definir `ISchemaResolver`:
   ```php
   interface ISchemaResolver {
       public function getColumnOrdinals(string $tableName): array;
   }
   ```

2. Implementar `FirebirdSchemaResolver`:
   - Recebe conexão Firebird no construtor
   - Executa query a `RDB$RELATION_FIELDS`
   - Retorna `['nome' => 1, 'idade' => 2, ...]`
   - Cache interno estático por `strtoupper($tableName)`

3. Adicionar setter opcional ao `Builder`:
   ```php
   private ?ISchemaResolver $schemaResolver = null;

   public function withSchemaResolver(ISchemaResolver $resolver): static
   {
       $this->schemaResolver = $resolver;
       return $this;
   }
   ```

4. Em `resolveOrderOrdinal()`, quando SELECT é `*` e `$schemaResolver !== null`:
   - Extrai nome da primeira tabela de `$this->query->from`
   - Chama `$this->schemaResolver->getColumnOrdinals($tableName)`
   - Resolve o ordinal a partir do array retornado

---

### Fase 3 — Testes e Documentação

**Casos de teste obrigatórios:**

| Cenário | Entrada | Saída esperada |
|---|---|---|
| Colunas explícitas + UNION | `orderBy('nome')` | `ORDER BY 1` |
| Alias + UNION | `orderBy('n')` com `SELECT nome AS n` | `ORDER BY 1` |
| Ordinal direto + UNION | `orderBy('2')` | `ORDER BY 2` |
| SELECT * + UNION sem resolver | `orderBy('nome')` | Exceção com mensagem orientativa |
| SELECT * + UNION com resolver | `orderBy('nome')` + DDL lookup | `ORDER BY 1` |
| Sem UNION | `orderBy('nome')` | `ORDER BY nome` *(comportamento atual)* |
| Função sem alias + UNION | `orderBy('COUNT(id)')` | `ORDER BY COUNT(id)` *(pass-through)* |

---

## Resumo de Decisões Arquiteturais

| Decisão | Justificativa |
|---|---|
| Resolução estática tem precedência sobre DDL | Zero I/O para o caso mais comum (colunas explícitas) |
| DDL lookup é opt-in via `ISchemaResolver` | Builder permanece utilizável sem conexão |
| Cache estático por tabela | Elimina round-trips repetidos sem necessidade de infraestrutura externa |
| Primeira tabela do FROM como referência | Alinhado ao padrão SQL (ordinals referem-se ao primeiro SELECT) |
| Pass-through quando não resolve | Falha explícita no banco é preferível a silenciosamente gerar SQL errado |
| Ativação apenas em contexto UNION | Sem impacto em queries sem UNION — zero breaking change |
