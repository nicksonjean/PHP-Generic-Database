# Análise: JSONConnection e JSONQueryBuilder como base para engines flat file

## Objetivo

Avaliar se `JSONConnection` e `JSONQueryBuilder` são genéricas o suficiente para servirem de base ao ajuste ou criação de:

- **CSVConnection** e **CSVQueryBuilder**
- **YAMLConnection** e **YAMLQueryBuilder**
- **INIConnection** e **INIQueryBuilder**
- **XMLConnection** e **XMLQueryBuilder**
- **NeonConnection** e **NeonQueryBuilder**

---

## 1. Visão geral do que já existe

### 1.1 JSON (referência)

| Componente | Descrição |
|------------|-----------|
| **JSONConnection** | Conexão com IConnection; handlers (DSN, Attributes, Options, Arguments, Structure, Statements, Fetch, Transactions, Report). |
| **JSONQueryBuilder** | Fluent API (select, from, join, innerJoin, on, where, group, having, order, limit, distinct). Execução via `query(buildRaw())` → FetchHandler. |
| **StructureHandler** | Mount via Schema.ini, `load`/`save` em `*.json`, `getTablePath`, `getTables`. |
| **FetchHandler** | Parse SQL-like (SELECT, JOIN, GROUP BY, HAVING, ORDER BY, LIMIT, DISTINCT, agregados); uso de `DataProcessor` e `Schema` para tipos. |
| **StatementsHandler** | Detecta tipo (SELECT/INSERT/UPDATE/DELETE), delega SELECT ao FetchHandler, CRUD ao `DataProcessor`. |

### 1.2 CSV

| Componente | Descrição |
|------------|-----------|
| **CSVConnection** | Conexão mais enxuta; FetchHandler, StatementsHandler; Schema.ini; `load`/`save` em CSV. |
| **CSVQueryBuilder** | select, from, distinct, where, etc.; **join/on/innerJoin etc. são no-op** (retornam `self`). Sem execução via SQL construído. |
| **FetchHandler** | Estende `AbstractFlatFileFetch`; `executeStoredQuery` retorna `getData()`. Sem parse de SQL. |

### 1.3 YAML e XML

- **YAMLConnection** / **XMLConnection**: existem como engines.
- **YAMLQueryBuilder** / **XMLQueryBuilder**: existem, com `getAllMetadata` e estrutura básica, mas sem o nível de completude do JSON (ex.: JOIN, GROUP BY, etc.).

### 1.4 INI e NEON

- **INI** e **NEON**: há *parsers* (`Helpers\Parsers\INI`, `Helpers\Parsers\NEON`) usados para configuração (argumentos, etc.).
- **Não existem** `INIConnection`, `NeonConnection`, `INIQueryBuilder` ou `NeonQueryBuilder`. Seriam engines novas.

---

## 2. O que é genérico e o que é específico

### 2.1 Genérico (reutilizável entre flat files)

- **`GenericDatabase\Generic\FlatFiles\DataProcessor`**: filtros, ordenação, alteração in-memory. Recebe `array`; não depende de JSON/CSV.
- **`Helpers\Parsers\Schema`**: Schema.ini com formatos `JSONDelimited`, `CSVDelimited`, `XMLDelimited`, `YAMLDelimited`. Já pensado para vários formatos.
- **Contrato IConnection**: `query`, `prepare`, `exec`, `fetch`, `fetchAll`, `getAllMetadata`, `getAffectedRows`, etc.
- **Contrato IQueryBuilder**: `select`, `from`, `where`, `order`, `limit`, etc.
- **AbstractFlatFileFetch**: cursor, `resultSet`, `clearCache`; base para Fetch handlers de flat file.
- **Padrão runOnce → `query(buildRaw())`**: o JSON QB executa a query montada via conexão; o mesmo padrão pode ser usado por outros QBs.

### 2.2 Específico por formato

- **Estrutura de arquivo**: extensão (`.json`, `.csv`, etc.), formato (linhas de objeto JSON, CSV, YAML, XML, INI, NEON).
- **StructureHandler** (ou equivalente): `load`/`save`, `getTablePath`, `getTables`, resolução de nomes. Cada engine implementa leitura/escrita no seu formato.
- **FetchHandler** (para SELECT):
  - JSON: parse completo de SQL-like (JOIN, GROUP BY, HAVING, agregados, etc.) e execução em cima de dados em array.
  - CSV: apenas `executeStoredQuery` → `getData()`; sem parse de SQL.
- **QueryBuilder Builder/Clause/Criteria**: no JSON, montam SQL e alimentam o FetchHandler. No CSV, join/on etc. não alteram a query.

---

## 3. JSONConnection e JSONQueryBuilder são genéricos o suficiente?

### 3.1 JSONConnection

- **Sim, como modelo de arquitetura**: padrão de handlers (Structure, Statements, Fetch), uso de Schema.ini, `query`/`prepare`/`exec` delegando aos handlers, `getAffectedRows` em INSERT/UPDATE/DELETE.
- **Não é “agnóstico de formato”**: depende de `StructureHandler` que lê/escreve `*.json` e monta estrutura a partir disso. Para CSV/YAML/XML/INI/Neon é preciso **StructureHandler (ou similar) específico** por formato.
- **Conclusão**: JSONConnection serve de **base arquitetural** (interface, fluxo, handlers). A lógica de I/O e de estrutura deve ser trocada por implementações por formato.

### 3.2 JSONQueryBuilder

- **Sim, como modelo de API e fluxo**: select/from/join/on/where/group/having/order/limit/distinct; `buildRaw()` → `query()` → FetchHandler.
- A **construção da query** (Builder, Clause, Criteria) não depende de JSON em si; só da string SQL-like produzida.
- A **execução** depende do **FetchHandler da conexão** suportar o SQL gerado. O FetchHandler do JSON suporta; o do CSV, hoje, não.
- **Conclusão**: JSONQueryBuilder é uma boa **base de API e de fluxo**. Para outro engine, basta que a Connection correspondente tenha um FetchHandler capaz de interpretar as mesmas construções (ou um subconjunto).

---

## 4. Uso como base para cada engine

### 4.1 CSVConnection e CSVQueryBuilder

- **Connection**: A estrutura geral (FetchHandler, StatementsHandler, Schema.ini, `getData`/`load`/`save`) já é próxima do JSON. O que muda é **como** os dados são carregados e salvos (CSV em vez de JSON).
- **QueryBuilder**: Hoje o CSV QB não executa join/on; são no-op. Para equiparar ao JSON:
  1. Replicar o padrão runOnce → `query(buildRaw())`.
  2. Implementar **join/on** no Clause + Builder (como no JSON), gerando `JOIN`/`INNER JOIN` etc.
  3. **Estender o FetchHandler do CSV** para parse e execução de SQL-like (ou reutilizar/sharear a lógica do JSON FetchHandler, se for extraída para algo genérico).
- **Estrutura de dados**: CSV normalmente é “uma tabela por arquivo”. JOIN entre arquivos CSV exige convenção (ex.: diretório = “database”, um `.csv` por tabela) e um FetchHandler que faça o mesmo que o do JSON (load de múltiplos “tabelas”, join em memória). O **modelo** do JSON é reutilizável; a **implementação** de load/save é específica de CSV.

### 4.2 YAMLConnection e YAMLQueryBuilder

- **Connection**: Mesmo raciocínio do CSV: mesma arquitetura geral, **StructureHandler** (ou equivalente) que carregue/salve YAML (ex.: `*.yaml` por “tabela”).
- **QueryBuilder**: Idem CSV; hoje é básico. Usar JSON QB como base (runOnce, buildRaw, join/on/group/having/order/limit) e adaptar ao FetchHandler do YAML.
- **Formato**: YAML pode representar listas de objetos de forma análoga a JSON; o **DataProcessor** e a lógica de agregados/jointuras em array continuam válidos.

### 4.3 XMLConnection e XMLQueryBuilder

- **Connection**: Arquitetura semelhante; **StructureHandler** para XML (ex.: um XML por “tabela”, ou seções/entidades mapeadas para tabelas).
- **QueryBuilder**: Igual: usar JSON como base de API e fluxo; FetchHandler do XML deve conseguir interpretar o SQL-like gerado.
- **Formato**: XML é mais idiomático (elementos, atributos). É preciso definir convenção (ex.: um elemento por linha, ou um documento = uma tabela) e transformar em `array` para o **DataProcessor** e para a lógica atual de JOIN/GROUP BY, etc.

### 4.4 INIConnection e INIQueryBuilder

- **Connection**: Nova engine. Seguir o desenho da JSONConnection: handlers (Structure, Statements, Fetch), Schema.ini, `query`/`prepare`/`exec`.
- **StructureHandler**: Carregar/salvar INI; definir convenção (ex.: seção = linha, chave = coluna; ou um INI por “tabela”). Já existe `Helpers\Parsers\INI`; usar como base de parse.
- **QueryBuilder**: Novo; copiar modelo do JSON QB e conectar à INIConnection. FetchHandler do INI precisaria aceitar o mesmo SQL-like (ou um subconjunto).

### 4.5 NeonConnection e NeonQueryBuilder

- **Connection**: Nova engine, mesmo padrão (handlers, Schema.ini, etc.).
- **StructureHandler**: Carregar/salvar NEON; convenção de “tabelas” (ex.: arquivos ou estruturas aninhadas). `Helpers\Parsers\NEON` já existe.
- **QueryBuilder**: Novo; mesmo esquema do INI: usar JSON QB como base, FetchHandler do Neon interpretando o SQL-like.

---

## 5. Recomendações

### 5.1 O que reutilizar diretamente

- **DataProcessor**: já genérico; manter para todos.
- **Schema.ini e `Helpers\Parsers\Schema`**: já multipropósito; usar em todas as engines flat file.
- **Padrão runOnce → `query(buildRaw())`** no QueryBuilder: adotar em CSV, YAML, XML e, no futuro, INI e Neon.
- **Builder/Clause/Criteria do JSON** (e trechos do FetchHandler): usar como **referência** para implementar join/on/group/having/order/limit em outros QBs e FetchHandlers.

### 5.2 O que adaptar por engine

- **StructureHandler** (ou equivalente): sempre específico do formato (extensão, parse, como montar `getTables`, `load`, `save`).
- **FetchHandler**: depende do nível de suporte desejado:
  - Se o objetivo é **paridade com JSON** (JOIN, GROUP BY, HAVING, etc.): implementar parse + execução semelhantes (ou extrair lógica compartilhada).
  - Se for apenas **SELECT simples** (uma “tabela”): manter estilo atual do CSV (ex.: `executeStoredQuery` → `getData()`).

### 5.3 Passos sugeridos para novas engines (INI, Neon) ou para evoluir as atuais (CSV, YAML, XML)

1. **Connection**: copiar a **estrutura** da JSONConnection (handlers, fluxo query/prepare/exec), trocando apenas o que mexe em I/O e estrutura (ex.: StructureHandler).
2. **StructureHandler**: implementar `load`, `save`, `getTablePath`, `getTables` para o formato (INI, NEON, ou refinamento de CSV/YAML/XML).
3. **QueryBuilder**: copiar a **API e o fluxo** do JSONQueryBuilder (incl. join, on, group, having, etc.); garantir que `buildRaw()` produza SQL-like compatível com o FetchHandler da engine.
4. **FetchHandler**: 
   - Ou reutilizar/estender a lógica de parse do JSON (se for extraída para um módulo comum), 
   - Ou implementar parse/execução próprios que consumam os mesmos SQL-like e alimentem o **DataProcessor** com arrays.

### 5.4 Resumo

| Pergunta | Resposta |
|----------|----------|
| JSONConnection é genérica o suficiente para basear outras engines? | **Sim**, como **modelo arquitetural** (handlers, Schema.ini, contrato IConnection). O I/O e a estrutura devem ser implementados por formato. |
| JSONQueryBuilder é genérica o suficiente? | **Sim**, como **modelo de API e de fluxo** (runOnce, buildRaw, query). A execução depende de um FetchHandler que entenda o SQL-like gerado. |
| Podem servir de base para CSV/YAML/INI/XML/Neon? | **Sim**. Em todos os casos: reutilizar estrutura da Connection, **DataProcessor** e Schema; implementar StructureHandler e FetchHandler específicos; usar o JSON QB como base para a API e o fluxo do QueryBuilder. |

---

## 6. Referências no código

- **JSON**  
  - `src/Engine/JSONConnection.php`  
  - `src/Engine/JSONQueryBuilder.php`  
  - `src/Engine/JSON/Connection/Structure/StructureHandler.php`  
  - `src/Engine/JSON/Connection/Fetch/FetchHandler.php`  
  - `src/Engine/JSON/QueryBuilder/Builder.php`, `Clause.php`, `Criteria.php`

- **CSV**  
  - `src/Engine/CSVConnection.php`  
  - `src/Engine/CSVQueryBuilder.php`  
  - `src/Engine/CSV/Connection/Fetch/FetchHandler.php`  
  - `src/Engine/CSV/QueryBuilder/Builder.php`, `Clause.php`

- **Genérico**  
  - `src/Generic/FlatFiles/DataProcessor.php`  
  - `src/Helpers/Parsers/Schema.php`  
  - `src/Abstract/AbstractFlatFileFetch.php`

- **Parsers (INI, NEON)**  
  - `src/Helpers/Parsers/INI.php`  
  - `src/Helpers/Parsers/NEON.php`
