# Composer Scripts - Documentação Dev vs Prod

Este documento explica quais scripts do `composer.json` são apenas para desenvolvimento e quais podem ser usados em produção.

## 📋 Resposta Direta

**Sim, a maioria dos scripts são apenas para desenvolvimento.** O Composer **não possui separação nativa** de scripts entre dev e prod, mas a solução implementada torna os scripts seguros para produção.

## 🔵 Scripts de PRODUÇÃO (sempre disponíveis)

Estes scripts **NÃO dependem** de `require-dev` e funcionam mesmo com `composer install --no-dev`:

| Script | Descrição | Quando Usar |
|--------|-----------|-------------|
| `run:env` | Inicializa `.env` a partir de `.env.example` | Após clone/update (via `post-update-cmd`) |
| `clear:cache` | Remove diretório `cache/` | Limpeza de cache em produção |

## 🟡 Scripts de DESENVOLVIMENTO (requerem `require-dev`)

Estes scripts **DEPENDEM** de ferramentas em `require-dev` e só funcionam com `composer install` (sem `--no-dev`):

### Documentação
- `docs`, `run:docs`, `run:docs:phar` → Gera documentação (Doctum)
- `clear:docs` → Remove diretório `docs/`

### Análise de Código / Lint
- `lint`, `phpcs`, `phpcbf`, `phpmd`, `phpstan`, `phplint` → Análise estática
- `phpcs:config` → Configura PHPCS (executado em `post-install-cmd` de forma condicional)

### Testes
- `test`, `run:test`, `test:coverage`, `run:test:coverage`, `run:test:migrate` → PHPUnit

### Diagramas
- `mermaid:class-diagram`, `mermaid:flowchart` → Geração de diagramas

### Git Hooks
- `grumphp` → Executa git hooks de qualidade de código

### Limpeza (Dev)
- `clear:test:coverage`, `clear:build` → Remove artefatos de dev
- `clear`, `setup`, `clear:vendor`, `clear:lock`, `clear:git` → Setup/limpeza de ambiente dev

**Nota**: Todos os scripts `clear:*` usam o script `scripts/developer/secure_eraser_cli.php` que:
- Remove arquivos individuais de forma segura
- Remove diretórios recursivamente (incluindo subdiretórios)
- Usa apenas PHP nativo (sem `exec()` ou chamadas de sistema)
- Funciona em Windows, Linux e Mac
- Veja mais detalhes em [`readme/SECURE-ERASER-CLI.md`](SECURE-ERASER-CLI.md)

### Limpeza (Produção)
- `clear:env` → Remove `.env` (menos comum em prod)

## ⚙️ Scripts Automáticos (Hooks)

### `post-install-cmd`
**Executado automaticamente em `composer install`**

```json
"post-install-cmd": [
  "php -r \"if(file_exists('vendor/bin/phpcs')) { ... }\""
]
```

**Comportamento:**
- ✅ **DEV**: Se `vendor/bin/phpcs` existir → configura PHPCS
- ✅ **PROD**: Se não existir → **silenciosamente ignora** (não falha)

### `post-update-cmd`
**Executado automaticamente em `composer update`**

```json
"post-update-cmd": [
  "@run:env"
]
```

**Comportamento:**
- ✅ **DEV**: Executa PHP inline → cria `.env` se não existir
- ✅ **PROD**: Executa PHP inline → cria `.env` se não existir (útil!)

## 🛡️ Proteção em Produção

### Solução Implementada

1. **`post-install-cmd` é condicional:**
   - Verifica se `vendor/bin/phpcs` existe antes de executar
   - Se não existir (prod com `--no-dev`), **silenciosamente ignora**

2. **`post-update-cmd` sempre funciona:**
   - Usa PHP inline que não depende de `require-dev`
   - Útil em produção para criar `.env` automaticamente

3. **Scripts manuais:**
   - Se você executar `composer test` em produção, vai falhar (esperado)
   - Não são executados automaticamente em prod

## 📊 Tabela de Compatibilidade

| Script | Requer `--no-dev`? | Funciona em Prod? | Executado Automaticamente? |
|--------|-------------------|-------------------|---------------------------|
| `run:env` | ❌ Não | ✅ Sim | ✅ `post-update-cmd` |
| `clear:cache` | ❌ Não | ✅ Sim | ❌ Manual |
| `post-install-cmd` | ✅ Condicional | ✅ Sim (ignora se não existir) | ✅ Automático |
| `phpcs:config` | ✅ Sim | ❌ Não | ✅ Via `post-install-cmd` (condicional) |
| `test`, `lint`, `docs` | ✅ Sim | ❌ Não | ❌ Manual |
| `clear:*` (dev) | ❌ Não (PHP inline) | ⚠️ Funciona mas não é necessário | ❌ Manual |

## 🚀 Como Usar em Produção

### Instalação Normal (com dev dependencies)
```bash
composer install
# Executa post-install-cmd → configura PHPCS
# Executa post-update-cmd → cria .env
```

### Instalação em Produção (sem dev dependencies)
```bash
composer install --no-dev --optimize-autoloader
# post-install-cmd → ignora PHPCS (não existe)
# post-update-cmd → cria .env (funciona!)
```

### Atualização em Produção
```bash
composer update --no-dev --optimize-autoloader
# post-update-cmd → cria .env (funciona!)
```

## ✅ Conclusão

**A solução atual é segura para produção:**

1. ✅ `post-install-cmd` é condicional (não falha em prod)
2. ✅ `post-update-cmd` usa `run:env` que funciona em prod
3. ✅ Scripts de dev não são executados automaticamente em prod
4. ✅ Se executar manualmente scripts de dev em prod, vai falhar (esperado)

**Não é necessário separar os scripts**, pois:
- Scripts automáticos já são condicionais/seguros
- Scripts manuais de dev raramente são executados em prod
- O Composer não possui separação nativa de scripts

## 📝 Recomendações

1. **Em produção, sempre use `--no-dev`:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Scripts úteis em produção:**
   - `composer run:env` → Inicializar `.env`
   - `composer clear:cache` → Limpar cache

3. **Scripts apenas para dev (não usar em prod):**
   - `composer test`, `composer lint`, `composer docs`, etc.
