# Análise: Viabilidade de Concentrar Diferenças de Formato em 3 Arquivos

## Objetivo

Avaliar se é viável **copiar toda a pasta `src/Engine/JSON`**, alterar nomes de classes e namespaces, e concentrar as diferenças de formato (JSON → CSV, INI, etc.) **apenas** em:

1. `src/Generic/FlatFiles/DataProcessor.php`
2. `src/Abstract/AbstractFlatFileFetch.php`
3. `src/Abstract/AbstractFlatFileStatements.php`

---

## 1. Resultado Principal: Os 3 Arquivos Já São Genéricos

### 1.1 DataProcessor.php

**Status: 100% genérico**

- Trabalha exclusivamente com `array` de linhas (associative arrays).
- Não há menção a JSON, CSV ou qualquer formato de arquivo.
- Operações: `select`, `where`, `orderBy`, `limit`, `insert`, `update`, `delete`, `groupBy`, `aggregate`, `distinct`.
- **Conclusão**: Não requer alterações para suportar CSV. Funciona para qualquer formato que produza `array` de linhas.

### 1.2 AbstractFlatFileFetch.php

**Status: 100% genérico**

- Trabalha com `resultSet` como `array` e cursor.
- Métodos `internalFetchAssoc`, `internalFetchNum`, `internalFetchBoth`, etc. operam sobre array.
- Método abstrato `executeStoredQuery(): array` — a implementação concreta é responsável por produzir o array.
- **Conclusão**: Não contém lógica de formato. O formato é definido por quem implementa `executeStoredQuery()`.

### 1.3 AbstractFlatFileStatements.php

**Status: 100% genérico**

- Gerencia metadata (`fetchedRows`, `lastInsertId`), detecção de tipo de query (SELECT, INSERT, etc.), `quote`, `parse`, `bindParam`.
- Não referencia JSON, CSV ou parsing de arquivos.
- **Conclusão**: Totalmente independente de formato.

---

## 2. Onde Está o Código Específico de Formato?

As diferenças de formato **não estão** nos 3 arquivos citados. Estão em:

| Arquivo | Responsabilidade específica de JSON |
|---------|-------------------------------------|
| `StructureHandler.php` | `load()`: `json_decode($content, true)` |
| | `save()`: `json_encode($data, $flags)` |
| | `getTablePath()`: extensão `.json` |
| | `scanTables()`: `glob('*.json')` |
| | `save()`: usa `JSON::getDefaultEncodingFlags()` |
| `FetchStrategy.php` | `cacheResource()`: `json_decode($resource, true)` quando resource é string |
| `JSON.php` | Constantes `ATTR_JSON_*`, `getDefaultEncodingFlags()` com `JSON_PRETTY_PRINT`, etc. |

### 2.1 Fluxo de Dados

```
Arquivo físico (.json / .csv / ...)
         ↓
  StructureHandler.load()   ← ÚNICO PONTO DE PARSING NO CARREGAMENTO
         ↓
  array de linhas
         ↓
  DataProcessor / FetchHandler / StatementsHandler  ← Já genéricos
```

- **DataProcessor**: recebe array, retorna array.
- **AbstractFlatFileFetch**: recebe array via `executeStoredQuery()`, retorna linhas.
- **AbstractFlatFileStatements**: usa DataProcessor com array.

O parsing de formato ocorre apenas em **StructureHandler** (e marginalmente em FetchStrategy).

---

## 3. Viabilidade: Copiar JSON e Adaptar para CSV

### 3.1 Abordagem "copiar pasta e alterar"

| Etapa | Viabilidade | Esforço |
|-------|-------------|---------|
| Copiar `src/Engine/JSON` → `src/Engine/CSV` | ✅ | Baixo |
| Renomear classes e namespaces (JSON → CSV) | ✅ | Baixo |
| Alterar DataProcessor, AbstractFlatFileFetch, AbstractFlatFileStatements | ❌ | Nenhum (já genéricos) |
| Implementar parsing CSV em algum lugar | ✅ | Médio |

### 3.2 Onde implementar o parsing CSV

**Opção A – StructureHandler específico (recomendado)**

- Criar `CSV\Connection\Structure\StructureHandler`.
- Em `load()`: usar `fgetcsv()` ou `str_getcsv()` em vez de `json_decode()`.
- Em `save()`: usar `fputcsv()` em vez de `json_encode()`.
- Em `getTablePath()`: extensão `.csv`.
- Em `scanTables()`: `glob('*.csv')`.

**Opção B – Parser injetável (mais genérico)**

- Extrair interface `IFlatFileParser` com `decode(string $content): array` e `encode(array $data): string`.
- StructureHandler receberia o parser por injeção.
- Para CSV: `CSVParser` implementando a interface.
- Permite reutilizar o mesmo StructureHandler com parsers diferentes.

### 3.3 FetchStrategy

- Em JSON: `cacheResource()` usa `json_decode()` quando o resource é string.
- Para CSV: pode ignorar esse ramo (CSV não usa esse cache de string) ou implementar um decoder CSV equivalente.
- Impacto: baixo (um `elseif` ou branch pequeno).

---

## 4. Impacto e Esforço Estimado

### 4.1 Se NÃO for extrair lógica comum

| Ação | Impacto | Arquivos afetados |
|------|---------|-------------------|
| Copiar pasta JSON → CSV | Baixo | Nova pasta `Engine/CSV` |
| Renomear classes/namespaces | Baixo | Todos os arquivos da nova pasta |
| Implementar CSV em StructureHandler | Médio | 1 arquivo (StructureHandler) |
| Ajustar FetchStrategy | Baixo | 1 arquivo |
| Criar CSV.php (constantes) | Baixo | 1 arquivo |
| **Total** | **Médio** | ~15–20 arquivos (maioria renomeação) |

### 4.2 Se for extrair parser para interface

| Ação | Impacto | Arquivos afetados |
|------|---------|-------------------|
| Criar interface IFlatFileParser | Baixo | 1 novo |
| Refatorar StructureHandler para usar parser | Médio | 1–2 |
| Implementar JSONParser, CSVParser | Médio | 2 novos |
| Copiar e adaptar engine CSV | Baixo | Nova pasta |
| **Total** | **Médio-alto** | Mais arquivos, mas melhor reuso |

---

## 5. Respostas Diretas

### Os 3 arquivos são genéricos o suficiente?

Sim. Eles já são genéricos e não precisam de mudanças para CSV. As diferenças de formato ficam fora deles.

### É possível concentrar as diferenças APENAS nesses 3 arquivos?

Não, porque eles não contêm lógica específica de formato. A concentração natural das diferenças é em:

1. **StructureHandler** (load/save/scan) – principal
2. **FetchStrategy** (cacheResource) – secundário
3. **Classe de constantes** (JSON.php / CSV.php) – atributos de formato

### O impacto para ter CSV funcionando é muito grande?

Não. O impacto é médio e previsível:

- Copiar pasta e renomear: esforço mecânico.
- StructureHandler com `fgetcsv`/`fputcsv`: implementação direta.
- DataProcessor, AbstractFlatFileFetch e AbstractFlatFileStatements: sem alterações.

### Schema.ini e CSV

O `Helpers\Parsers\Schema` já suporta `Format=CSVDelimited` e `Format=TabDelimited`. O `applySchema()` trabalha com arrays e é independente do formato. CSV pode usar Schema.ini normalmente.

---

## 6. Recomendação

1. **Não alterar** DataProcessor, AbstractFlatFileFetch e AbstractFlatFileStatements para suportar CSV — eles já servem para isso.

2. **Copiar** a pasta JSON para CSV e:
   - Renomear classes e namespaces.
   - Implementar `StructureHandler` com parsing CSV em `load()` e `save()`.
   - Adaptar `FetchStrategy` conforme necessário.
   - Criar `CSV.php` com constantes de formato CSV (delimitador, encoding, etc.).

3. **Opcional (futuro)**:
   - Extrair interface `IFlatFileParser` e implementar `JSONParser` e `CSVParser`.
   - Fazer o StructureHandler receber o parser por injeção, reduzindo duplicação entre engines.

---

## 7. Resumo Executivo

| Pergunta | Resposta |
|----------|----------|
| Os 3 arquivos são genéricos? | Sim, totalmente. |
| Diferenças concentradas só nesses 3? | Não; eles já são genéricos. O parsing fica em StructureHandler. |
| Viabilidade de copiar JSON → CSV? | Alta. |
| Impacto total | Médio: copiar/renomear + implementar load/save CSV. |
| Necessidade de alterar DataProcessor, AbstractFlatFileFetch, AbstractFlatFileStatements? | Nenhuma. |
