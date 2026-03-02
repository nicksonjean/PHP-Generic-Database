# PHP Generic Database — Assistente de Conexão

> 🇺🇸 [Read in English](README.md)

Um assistente web para configurar, testar e executar queries em qualquer banco de dados suportado pelo **PHP Generic Database**. Suporta múltiplos engines (Native, PDO, ODBC, PDO+ODBC, Flat Files), interface bilíngue (Inglês / Português) e um conversor completo de SQL para QueryBuilder.

---

## Índice

1. [Visão Geral](#visão-geral)
2. [Estrutura de Diretórios](#estrutura-de-diretórios)
3. [Funcionalidades](#funcionalidades)
4. [Etapas do Wizard](#etapas-do-wizard)
5. [Gerenciamento de Conexões](#gerenciamento-de-conexões)
6. [Executor de Queries (Etapa 2)](#executor-de-queries-etapa-2)
7. [Query Builder (Etapa 3)](#query-builder-etapa-3)
8. [Configurações](#configurações)
9. [Arquivos de Dados](#arquivos-de-dados)
10. [Constantes de Caminho](#constantes-de-caminho)
11. [Internacionalização](#internacionalização)
12. [Referência da API](#referência-da-api)
13. [Estendendo o Wizard](#estendendo-o-wizard)

---

## Visão Geral

O Wizard é uma aplicação PHP autocontida localizada em `wizard/`. **Não** requer framework — apenas PHP ≥ 8.0 e a biblioteca PHP Generic Database instalada via Composer.

```
wizard/
├── index.php          ← ponto de entrada (inicializa template + i18n)
├── api.php            ← API REST JSON consumida pelo wizard.js
├── assets/
│   ├── wizard.css     ← estilos
│   └── icons/         ← 15 ícones SVG de bancos de dados
├── data/              ← estado persistente JSON/TOML (recomendado no .gitignore)
│   ├── settings.toml        ← configurações globais (paginação + caminhos de arquivos)
│   ├── active_profile.json  ← conexão ativa atual
│   ├── profiles.json        ← todas as conexões salvas (Custom + Preset)
│   ├── queries.json         ← queries SQL salvas
│   ├── query_history.json   ← histórico de execução (máx. 100)
│   ├── builders.json        ← snippets QueryBuilder salvos
│   ├── builder_history.json ← histórico de execução do builder (máx. 100)
│   ├── draft.json           ← rascunhos de SQL / QB
│   └── profiles/            ← arquivos env + PHP por conexão
│       ├── .custom_<nome>            ← variáveis de ambiente (KEY="value")
│       └── custom_<nome>.php         ← arquivo PHP de conexão gerado automaticamente
├── includes/
│   ├── Autoload.php   ← carregador de templates
│   ├── Functions.php  ← funções utilitárias (env, TOML, alertas…)
│   ├── Template.php   ← motor de templates PHP mínimo
│   └── Translator.php ← carregador i18n
├── js/
│   └── wizard.js      ← toda a lógica frontend (vanilla JS)
├── locales/
│   ├── en.php         ← strings em inglês
│   └── pt.php         ← strings em português
└── templates/
    └── index.php      ← template HTML principal (Bootstrap 5)
```

---

## Funcionalidades

| Funcionalidade | Descrição |
|---|---|
| **Multi-engine** | Native (MySQLi, PgSQL, SQLSrv, OCI, Firebird, SQLite), PDO, ODBC, PDO+ODBC, Flat Files (CSV, INI, JSON, NEON, XML, YAML) |
| **Conexões predefinidas** | Ative conexões baseadas em variáveis de ambiente sem preencher formulários |
| **Conexões customizadas** | Formulário completo com host/porta/database/usuário/senha/charset/opções |
| **Teste de conexão** | Valida as credenciais antes de salvar |
| **Gerenciar conexões** | Tabela com ações de ativar / editar / excluir |
| **Executor SQL raw** | Execute qualquer SQL na conexão ativa |
| **Prepared statements** | Placeholders `?`, `$1`, `:nome` com campos de parâmetros dinâmicos |
| **SQL → QueryBuilder** | Converte SQL para chamadas da API PHP QueryBuilder (suporta UNION, EXISTS, subqueries) |
| **Persistência de queries** | Salve, carregue e exclua queries nomeadas |
| **Histórico de execução** | Últimas 100 execuções de queries e builders com timestamp e contagem de linhas |
| **Sistema de rascunho** | Salve e restaure trabalhos em andamento (SQL / QB) |
| **Configurações** | Limites de paginação e caminhos de arquivos configuráveis, persistidos em `settings.toml` |
| **i18n** | Tradução completa EN / PT-BR com alternância via URL |
| **Modo de depuração** | Ativado via URL `?debug=1` ou checkbox na UI para expor objetos de conexão brutos |

---

## Etapas do Wizard

A UI está organizada em três etapas:

```
[ ① Conexão ] ──── [ ② Query ] ──── [ ③ Builder ]
```

As etapas 2 e 3 ficam desabilitadas até que uma conexão ativa seja configurada.

---

## Gerenciamento de Conexões

### Conexão Predefinida

Uma conexão predefinida usa variáveis de ambiente já presentes no arquivo `.env` do projeto. Nenhuma credencial é armazenada no wizard — o arquivo PHP gerado simplesmente lê `$_ENV`.

```
Módulo → Engine → Driver → Tipo de Instância → [Testar] → [Confirmar]
```

Módulos suportados: `Chainable`, `Fluent`, `StaticArgs`, `StaticArray`.

Tipos de instância:
- **Específico** — usa a classe de driver concreta (`MySQLiConnection`, `PDOConnection`, …)
- **Strategy / Facade** — usa a facade abstrata `Connection`

### Conexão Customizada

Uma conexão customizada armazena credenciais em `data/profiles/` como um par de arquivos:

| Arquivo | Conteúdo |
|---|---|
| `data/profiles/.custom_<nome>` | Arquivo env no estilo shell (`KEY="value"`) |
| `data/profiles/custom_<nome>.php` | Bootstrap PHP gerado automaticamente que lê o env e cria a conexão |

O wizard gera o arquivo PHP automaticamente. Você pode incluí-lo diretamente em seus scripts:

```php
require_once __DIR__ . '/wizard/data/profiles/custom_minha_conexao.php';
// $context é a instância GenericDatabase conectada
$qb = \GenericDatabase\Engine\MySQLi\QueryBuilder\Builder::with($context);
```

### Conexão Ativa

Apenas uma conexão está ativa por vez. É armazenada em `data/active_profile.json`:

```json
{
  "active_connection": {
    "source": "Custom",
    "name": "custom_fluent_native_mysqli",
    "module": "Chainable",
    "engine": "Native",
    "driver": "MySQLi",
    "type": "Specific",
    "env_file": "profiles/.custom_fluent_native_mysqli",
    "php_file": "profiles/custom_fluent_native_mysqli.php"
  }
}
```

Clique no badge da conexão ativa no breadcrumb para abrir um visualizador JSON desse arquivo.

---

## Executor de Queries (Etapa 2)

### Tipos de Query

| Tipo | Descrição |
|---|---|
| **SQL Raw** | Executado via `$conn->query($sql)` |
| **Prepared Statement** | Executado via `$conn->prepare($sql, ...$params)` |

### Formatos de Placeholder (Prepared)

| Formato | Exemplo |
|---|---|
| Interrogação | `WHERE id = ?` |
| Numerado | `WHERE id = $1` |
| Nomeado | `WHERE id = :id` |

Os campos de parâmetros dinâmicos são gerados automaticamente com base no número e tipo de placeholders encontrados no SQL.

### Resultados

Queries SELECT retornam uma tabela de resultados paginada. Queries DML retornam o número de linhas afetadas. Ambas exibem o tempo de execução em milissegundos.

### Salvando e Histórico

- **Salvar Query** — atribui um nome e persiste em `data/queries.json`
- **Salvar Rascunho** — apelido opcional, persistido em `data/draft.json`
- **Histórico** — as últimas 100 execuções são armazenadas em `data/query_history.json`

---

## Query Builder (Etapa 3)

Cole qualquer SQL na área de entrada e o wizard converte em tempo real para a API PHP QueryBuilder:

```sql
SELECT u.id, u.name FROM users u
WHERE EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)
ORDER BY u.name ASC LIMIT 10
```

↓ se torna ↓

```php
$qb = Builder::with($context)
    ->table('users', 'u')
    ->select('u.id', 'u.name')
    ->whereExists(fn($sub) => $sub->table('orders', 'o')->select(1)->where('o.user_id', '=', 'u.id'))
    ->orderBy('u.name', 'ASC')
    ->limit(10);
```

Construções SQL suportadas:

- `SELECT` com aliases e múltiplas tabelas
- `WHERE` / `AND` / `OR` com operadores (`=`, `!=`, `>`, `<`, `LIKE`, `IN`, `BETWEEN`, `IS NULL`, …)
- `EXISTS` / `NOT EXISTS` com subqueries correlacionadas
- `JOIN` (`INNER`, `LEFT`, `RIGHT`, `CROSS`)
- `GROUP BY` / `HAVING`
- `ORDER BY` (múltiplas colunas, ASC / DESC)
- `LIMIT` / `OFFSET`
- `UNION` / `UNION ALL`
- Subqueries aninhadas

Clique em **Executar** para rodar o código gerado diretamente na conexão ativa.

---

## Configurações

Todas as configurações globais são armazenadas em `data/settings.toml` e editáveis pelo modal **Opções → Configurações**. Ao salvar qualquer valor, a página é recarregada automaticamente para aplicar a nova configuração.

### Paginação

| Configuração | Padrão | Descrição |
|---|---|---|
| `queries_page_size` | 10 | Itens por página na lista de Queries Salvas |
| `history_page_size` | 10 | Itens por página no Histórico de Queries |
| `connections_page_size` | 10 | Itens por página na tabela de Gerenciar Conexões |
| `builders_page_size` | 10 | Itens por página na lista de Builders Salvos |
| `builder_history_page_size` | 10 | Itens por página no Histórico de Builder |

Intervalo válido: **1 – 100** por configuração.

### Caminhos dos Arquivos de Dados

| Configuração | Padrão | Descrição |
|---|---|---|
| `profiles_dir` | `/profiles` | Diretório para arquivos por conexão (relativo a `data/`) |
| `active_profile_file` | `/active_profile.json` | Metadados da conexão ativa |
| `profiles_file` | `/profiles.json` | Todas as conexões registradas |
| `queries_file` | `/queries.json` | Queries SQL salvas |
| `query_history_file` | `/query_history.json` | Histórico de execução de queries |
| `builders_file` | `/builders.json` | Snippets QueryBuilder salvos |
| `builder_history_file` | `/builder_history.json` | Histórico de execução do builder |
| `draft_file` | `/draft.json` | Rascunhos de SQL / QB |

Os valores de caminho devem começar com `/` e não podem conter `..`.

### Formato do settings.toml

```toml
# Wizard Configuration File
queries_page_size = 10
history_page_size = 10
connections_page_size = 10
builders_page_size = 10
builder_history_page_size = 10

profiles_dir = "/profiles"
active_profile_file = "/active_profile.json"
profiles_file = "/profiles.json"
queries_file = "/queries.json"
query_history_file = "/query_history.json"
builders_file = "/builders.json"
builder_history_file = "/builder_history.json"
draft_file = "/draft.json"
```

A leitura e escrita de TOML é feita por `read_toml_file()` e `write_toml_file()` em `includes/Functions.php` — sem biblioteca externa.

---

## Arquivos de Dados

Todo o estado é armazenado como arquivos simples dentro de `data/`. Recomenda-se adicionar `wizard/data/` ao `.gitignore` para evitar commitar credenciais.

| Arquivo | Formato | Finalidade |
|---|---|---|
| `settings.toml` | TOML | Configurações globais (paginação + caminhos) |
| `active_profile.json` | JSON | Metadados da conexão ativa |
| `profiles.json` | JSON | Todas as conexões registradas (Custom + Preset) |
| `queries.json` | JSON | Queries SQL salvas com nome |
| `query_history.json` | JSON | Últimas 100 queries executadas |
| `builders.json` | JSON | Snippets QueryBuilder salvos com nome |
| `builder_history.json` | JSON | Últimas 100 execuções do builder |
| `draft.json` | JSON | Rascunhos (SQL e QB não salvos) |
| `profiles/.custom_<nome>` | ENV | Credenciais por conexão |
| `profiles/custom_<nome>.php` | PHP | Bootstrap de conexão gerado automaticamente |
| `profiles/preset_<nome>.php` | PHP | Bootstrap de conexão predefinida |

---

## Constantes de Caminho

Apenas duas constantes são definidas estaticamente em `index.php` e `api.php`:

```php
define('WIZARD_DATA_DIR',    __DIR__ . '/data');
define('WIZARD_SETTINGS_FILE', WIZARD_DATA_DIR . '/settings.toml');
```

Todas as demais constantes `WIZARD_*` — `WIZARD_PROFILES_DIR`, `WIZARD_QUERIES_FILE`, etc. — são derivadas automaticamente em tempo de execução a partir do `settings.toml`. Valores de caminho (aqueles que começam com `/`) são expandidos para caminhos absolutos prefixando `WIZARD_DATA_DIR`:

```php
// Derivado automaticamente do settings.toml na inicialização:
// profiles_dir = "/profiles"  →  WIZARD_PROFILES_DIR = WIZARD_DATA_DIR . '/profiles'
// queries_file = "/queries.json"  →  WIZARD_QUERIES_FILE = WIZARD_DATA_DIR . '/queries.json'
// ...
```

Para realocar qualquer arquivo ou diretório, edite o valor em `settings.toml` pelo modal **Opções → Configurações** (ou diretamente no arquivo) — sem alterações no código PHP.

---

## Internacionalização

O idioma é selecionado via parâmetro de URL `lang` (`?lang=en` ou `?lang=pt`). Um alternador de idioma está disponível no dropdown Opções.

As strings de tradução ficam em `locales/en.php` e `locales/pt.php` como arrays PHP simples. A classe `Translator` (`includes/Translator.php`) carrega o arquivo adequado e resolve chaves com interpolação opcional `{{variavel}}`:

```php
$t = new Translator('pt');
echo $t->t('connection_created');          // "Conexão criada com sucesso!"
echo $t->t('query_rows', ['n' => 42]);    // "42 linhas"
```

Para adicionar um novo idioma, crie `locales/<codigo>.php` retornando um array com as mesmas chaves de `en.php`, depois adicione o código à lista de permissões em `index.php`:

```php
$lang = in_array($lang, ['en', 'pt', 'es']) ? $lang : 'en';
```

---

## Referência da API

Todas as chamadas de API vão para `api.php` e usam o parâmetro `action` (GET ou POST).

### `test_connection` · POST

Testa uma conexão de banco de dados sem salvá-la.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `module` | string | `Chainable`, `Fluent`, `StaticArgs`, `StaticArray` |
| `engine` | string | `native`, `pdo`, `odbc`, `pdo_odbc`, `flat_files` |
| `driver` | string | `mysql`, `pgsql`, `sqlsrv`, `oci`, `firebird`, `sqlite`, … |
| `instance_type` | string | `specific` ou `strategy` |
| `host`, `port`, `database`, `username`, `password`, `charset` | string | Credenciais de conexão |
| `options` | string | JSON ou opções `key => value` |

Resposta: `{ success, message, debug? }`

---

### `complete_connection` · POST

Salva uma nova conexão customizada e a define como ativa. Mesmos parâmetros que `test_connection`, mais `connection_name`.

Resposta: `{ success, message, files: { env, php }, config }`

---

### `update_connection` · POST

Atualiza uma conexão customizada existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `edit_connection_name` | string | Nome atual da conexão a atualizar |
| `connection_name` | string | Novo nome (pode ser o mesmo) |
| + todos os parâmetros de `complete_connection` | | |

Resposta: `{ success, message, config }`

---

### `confirm_preset` · POST

Ativa uma conexão predefinida.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `module` | string | Identificador do módulo |
| `engine` | string | Identificador do engine |
| `driver` | string | Identificador do driver |
| `instance_type` | string | `specific` ou `strategy` |

Resposta: `{ success, message, config }`

---

### `get_connections` · GET

Retorna todas as conexões salvas e o nome da conexão ativa atual.

Resposta: `{ connections: [], active_name: string }`

---

### `get_connection_fields` · GET

Retorna campos de formulário HTML para uma combinação `driver`/`engine`, pré-preenchidos com dados existentes quando `connection_name` é fornecido.

---

### `activate_connection` · POST

Define uma conexão salva como ativa.

| Parâmetro | Tipo |
|---|---|
| `connection_name` | string |

---

### `delete_connection` · POST

Exclui uma conexão customizada e todos os arquivos associados.

| Parâmetro | Tipo |
|---|---|
| `connection_name` | string |

---

### `execute_query` · POST

Executa uma query SQL na conexão ativa.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `sql` | string | SQL a executar |
| `query_type` | string | `raw` ou `prepared` |
| `placeholder_type` | string | `question`, `numbered`, `named` |
| `params` | array | Valores dos parâmetros |

Resposta: `{ success, rows?, count?, columns?, affected_rows?, duration_ms }`

---

### `save_query` · POST

Persiste uma query SQL com nome.

| Parâmetro | Tipo |
|---|---|
| `name` | string |
| `sql` | string |
| `query_type` | string |

---

### `delete_query` · POST / `get_queries` · GET

Gerencia queries salvas. `delete_query` requer `query_id`.

---

### `execute_builder` · POST

Executa código PHP QueryBuilder na conexão ativa.

| Parâmetro | Tipo |
|---|---|
| `code` | string |
| `sql` | string |

---

### `sql_to_qb` · POST

Converte SQL em código PHP QueryBuilder sem executar.

| Parâmetro | Tipo |
|---|---|
| `sql` | string |

Resposta: `{ success, code }`

---

### `get_settings` · GET

Retorna as configurações atuais.

Resposta: `{ success, settings: { queries_page_size, profiles_dir, … } }`

---

### `update_settings` · POST

Persiste um ou mais valores de configuração (cada um como campo POST).
- Valores de paginação fora do intervalo 1–100 são ignorados.
- Valores de caminho que não começam com `/` ou que contêm `..` são ignorados.

Resposta: `{ success, settings }`

---

### `clear_active_connection` · POST

Remove a seleção da conexão ativa sem excluir arquivos.

---

### `get_config` · GET

Retorna a configuração completa da conexão ativa (usado pelo modal do badge de conexão ativa).

---

## Estendendo o Wizard

### Adicionar um novo driver de banco de dados

1. Adicione a chave do driver a `$ENGINE_DRIVERS` em `api.php`
2. Adicione o nome do método a `$DRIVER_METHODS`
3. Adicione a lista de chaves env a `$DRIVER_ENV_KEYS`
4. Adicione o prefixo env a `$DRIVER_ENV_PREFIX`
5. Adicione o rótulo a `$DRIVER_LABELS`
6. Espelhe as mesmas adições no objeto `CONFIG` em `wizard.js`
7. Adicione um ícone SVG 32 × 32 em `assets/icons/` e registre-o em `CONFIG.driverIcons`

### Adicionar um novo idioma

1. Copie `locales/en.php` para `locales/<codigo>.php` e traduza os valores
2. Adicione `'<codigo>'` à verificação `in_array` em `index.php`
3. Adicione o rótulo de alternância de idioma (ex.: `'lang_switch' => 'Español'`) ao novo arquivo de locale

### Alterar o local de um arquivo de dados

Edite a chave de caminho relevante em `data/settings.toml` pelo modal **Opções → Configurações** (ou diretamente no arquivo). O novo caminho entra em vigor no próximo carregamento de página — sem alterações no código PHP.
