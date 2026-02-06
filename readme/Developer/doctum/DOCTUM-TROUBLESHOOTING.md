# Doctum - Troubleshooting: Travamento em 36% (PHP 8.0)

## 🐛 Problema Identificado

O Doctum está travando em **36% durante o rendering** na classe `GenericDatabase\Abstract\AbstractArguments` quando executado no **PHP 8.0**.

## 🔍 Causa Provável

### Match Expression Aninhado

A classe `AbstractArguments` usa **match expressions aninhados** (match dentro de match):

```php
// Linha 220-232 em src/Abstract/AbstractArguments.php
return match ($name) {
    'new' => match (true) {  // ⚠️ Match aninhado
        JSON::isValidJSON(...$argumentsFile) => ...,
        YAML::isValidYAML(...$argumentsFile) => ...,
        // ...
    },
    default => ...,
};
```

**Problema:** O parser do Doctum pode ter dificuldades com:
- Match expressions aninhados
- Spread operators (`...$argumentsFile`)
- Union types (`IConnection|string|int|bool|array|null`)
- PHP 8.0 específico

## ✅ Soluções Implementadas

### 1. Memory Limit e Timeout

**No `composer.json`:**
```json
"run:docs": "php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/doctum.php update ./doctum.php --ignore-parse-errors -vvv"
```

**No `doctum.php`:**
```php
ini_set('memory_limit', '-1');
set_time_limit(0);
```

### 2. Ignore Parse Errors

O flag `--ignore-parse-errors` permite que o Doctum continue mesmo com erros de parsing.

## 🔧 Soluções Alternativas

### Opção 1: Gerar Docs Localmente (Recomendado)

Se o problema persistir no container PHP 8.0, gere a documentação **localmente** ou em uma versão mais recente do PHP:

```bash
# Localmente (fora do container)
composer docs

# Ou em container PHP 8.3+
docker-compose exec php-8.3-apache composer docs
```

### Opção 2: Limpar Cache Antes de Gerar

```bash
# Limpar cache do Doctum
composer clear:cache
composer clear:docs

# Tentar novamente
composer docs
```

### Opção 3: Gerar Docs Incrementalmente

Se o problema persistir, você pode:

1. **Excluir a classe problemática temporariamente:**
   - Mover `AbstractArguments.php` para fora de `src/`
   - Gerar docs
   - Mover de volta

2. **Ou gerar docs em lotes:**
   - Documentar apenas partes específicas do projeto
   - Usar filtros no Finder do Doctum

### Opção 4: Usar Versão Mais Recente do Doctum

O problema pode ser resolvido em versões futuras do Doctum:

```bash
composer require --dev code-lts/doctum:^6.0  # Se disponível
```

## 📊 Status por Versão PHP

| Versão PHP | Doctum Funciona? | Notas |
|------------|------------------|-------|
| PHP 8.0    | ⚠️ Pode travar em 36% | Problema conhecido com match aninhado |
| PHP 8.1    | ✅ Deve funcionar | Melhor suporte a match expressions |
| PHP 8.2    | ✅ Deve funcionar | Suporte completo |
| PHP 8.3    | ✅ Deve funcionar | Suporte completo |
| PHP 8.4    | ✅ Deve funcionar | Suporte completo |

## 🔍 Como Diagnosticar

### Verificar se o problema é específico do PHP 8.0:

```bash
# Testar em PHP 8.0
docker-compose exec php-8.0-apache composer docs

# Testar em PHP 8.3
docker-compose exec php-8.3-apache composer docs
```

### Ver logs detalhados:

```bash
# Com verbosidade máxima
docker-compose exec php-8.0-apache composer run:docs
# Ou diretamente
docker-compose exec php-8.0-apache php -d memory_limit=-1 -d max_execution_time=0 vendor/bin/doctum.php update ./doctum.php --ignore-parse-errors -vvv
```

### Verificar memory/timeout:

```bash
# Dentro do container
docker-compose exec php-8.0-apache php -i | grep memory_limit
docker-compose exec php-8.0-apache php -i | grep max_execution_time
```

## 💡 Workaround Imediato

Se precisar gerar docs **agora**:

1. **Usar PHP 8.3+ para gerar docs:**
   ```bash
   docker-compose exec php-8.3-apache composer docs
   ```

2. **Ou gerar localmente (se tiver PHP instalado):**
   ```bash
   composer install
   composer docs
   ```

3. **Ou limpar cache e tentar novamente:**
   ```bash
   composer clear:cache
   composer clear:docs
   composer docs
   ```

## 📝 Recomendações

1. **Para desenvolvimento:** Use PHP 8.3+ para gerar docs (evita o problema)
2. **Para CI/CD:** Use PHP 8.3+ ou mais recente na pipeline de docs
3. **Para produção:** Docs não precisam ser gerados em produção
4. **Monitoramento:** Acompanhe atualizações do Doctum que possam resolver o problema

## 🔗 Referências

- [Doctum GitHub Issues](https://github.com/code-lts/doctum/issues)
- [PHP 8.0 Match Expression](https://www.php.net/manual/en/control-structures.match.php)
- [Doctum Documentation](https://code-lts.github.io/doctum/)
