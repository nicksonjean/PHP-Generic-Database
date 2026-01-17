# Secure Eraser CLI

## 📋 Visão Geral

O `secure_eraser_cli.php` é um script CLI robusto para remover arquivos e diretórios de forma segura usando apenas PHP nativo, sem dependências externas ou chamadas de sistema (`exec()`, `system()`, etc.).

## ✨ Funcionalidades

- ✅ **Remove arquivos individuais** de forma segura
- ✅ **Remove diretórios completos** recursivamente (incluindo subdiretórios e múltiplos arquivos)
- ✅ **Usa apenas PHP nativo** (`RecursiveIteratorIterator`, `unlink()`, `rmdir()`)
- ✅ **Cross-platform** (Windows, Linux, Mac)
- ✅ **Tratamento robusto de erros** com try/catch
- ✅ **Normaliza caminhos** automaticamente (suporta `/`, `\`, ou `DIRECTORY_SEPARATOR`)
- ✅ **Códigos de saída apropriados** para integração com scripts

## 🚀 Uso

### Sintaxe Básica

```bash
php scripts/developer/secure_eraser_cli.php --dest=/caminho/para/arquivo
php scripts/developer/secure_eraser_cli.php --dest=/caminho/para/diretorio
```

### Exemplos Práticos

#### Remover um Arquivo

```bash
# Remover composer.lock
php scripts/developer/secure_eraser_cli.php --dest=./composer.lock

# Remover .env
php scripts/developer/secure_eraser_cli.php --dest=./.env

# Remover arquivo de hook do Git
php scripts/developer/secure_eraser_cli.php --dest=./.git/hooks/pre-commit
```

#### Remover um Diretório (Recursivo)

```bash
# Remover diretório cache/
php scripts/developer/secure_eraser_cli.php --dest=./cache/

# Remover diretório docs/
php scripts/developer/secure_eraser_cli.php --dest=./docs/

# Remover diretório vendor/
php scripts/developer/secure_eraser_cli.php --dest=./vendor/

# Remover diretório build/ completo
php scripts/developer/secure_eraser_cli.php --dest=./build/
```

#### Via Docker (Recomendado)

```bash
# Via docker-compose
docker-compose exec php-8.3-apache php scripts/developer/secure_eraser_cli.php --dest=./cache/

# Ou criando container temporário
docker-compose run --rm php-8.3-apache php scripts/developer/secure_eraser_cli.php --dest=./cache/
```

## 📦 Integração com Composer

O script é usado nos comandos `clear:*` do `composer.json`:

```json
{
  "scripts": {
    "clear:cache": "php scripts/developer/secure_eraser_cli.php --dest=./cache/",
    "clear:docs": "php scripts/developer/secure_eraser_cli.php --dest=./docs/",
    "clear:vendor": "php scripts/developer/secure_eraser_cli.php --dest=./vendor/",
    "clear:lock": "php scripts/developer/secure_eraser_cli.php --dest=./composer.lock",
    "clear:env": "php scripts/developer/secure_eraser_cli.php --dest=./.env",
    "clear:git": [
      "php scripts/developer/secure_eraser_cli.php --dest=./.git/hooks/commit-msg",
      "php scripts/developer/secure_eraser_cli.php --dest=./.git/hooks/pre-commit"
    ]
  }
}
```

### Executar via Composer

```bash
# Limpar cache
composer clear:cache

# Limpar documentação
composer clear:docs

# Limpar vendor (cuidado!)
composer clear:vendor

# Limpar arquivo composer.lock
composer clear:lock

# Limpar arquivo .env
composer clear:env

# Limpar hooks do Git
composer clear:git
```

## 🔧 Funcionamento Interno

### Remoção de Diretórios

O script usa `RecursiveIteratorIterator` com `CHILD_FIRST` para:

1. **Iterar recursivamente** por todos os arquivos e subdiretórios
2. **Excluir arquivos primeiro** (`unlink()`)
3. **Excluir diretórios depois** (`rmdir()`), começando pelos mais profundos
4. **Finalmente excluir o diretório raiz**

```php
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($iterator as $file) {
    $path = $file->getPathname();
    if ($file->isDir()) {
        @rmdir($path);
    } else {
        @unlink($path);
    }
}
@rmdir($dir);
```

### Remoção de Arquivos

Para arquivos individuais, usa `unlink()` com tratamento de erros:

```php
if (is_file($file) && is_readable($file)) {
    return @unlink($file);
}
```

### Normalização de Caminhos

O script normaliza caminhos automaticamente para funcionar em qualquer sistema:

- Windows: `C:\projeto\cache\` ou `C:/projeto/cache/`
- Linux/Mac: `/projeto/cache/` ou `./cache/`
- Todos: Convertidos para `DIRECTORY_SEPARATOR` correto

## ⚠️ Comportamentos Importantes

### Caminho Não Existe

Se o caminho especificado **não existir**, o script:
- ✅ Retorna código de saída `0` (sucesso)
- ✅ Não gera erro
- ✅ Considera que "já está removido"

**Motivo**: Isso permite executar scripts de limpeza múltiplas vezes sem erro.

### Erro de Permissão

Se houver erro de permissão ao remover:
- ✅ Retorna código de saída `1` (erro)
- ✅ Mensagem de erro é enviada para `STDERR`
- ✅ Script para a execução

### Arquivos/Diretórios Especiais

O script **ignora**:
- `.` e `..` (SKIP_DOTS)
- Links simbólicos são tratados como arquivos normais
- Arquivos somente leitura podem falhar (depende das permissões do sistema)

## 🐛 Troubleshooting

### Erro: "Parâmetro --dest é obrigatório"

```bash
ERRO: Parâmetro --dest é obrigatório.
Uso: php secure_eraser_cli.php --dest=/caminho/para/arquivo ou /caminho/para/diretorio
```

**Solução**: Forneça o parâmetro `--dest`:

```bash
# ❌ Errado
php scripts/developer/secure_eraser_cli.php

# ✅ Correto
php scripts/developer/secure_eraser_cli.php --dest=./cache/
```

### Erro: "Caminho existe mas não é um arquivo ou diretório válido"

Isso pode ocorrer se:
- O caminho é um link simbólico quebrado
- O caminho é um tipo de arquivo especial não suportado

**Solução**: Verifique o caminho manualmente:

```bash
# Verificar o que é o caminho
ls -la ./caminho/problematico
file ./caminho/problematico

# Remover manualmente se necessário
rm -rf ./caminho/problematico  # Linux/Mac
rmdir /s /q .\caminho\problematico  # Windows
```

### Erro: "Permission denied" (Dentro do Docker)

Se executar dentro do Docker e receber erro de permissão:

**Solução**: Verifique as permissões do container:

```bash
# Verificar usuário atual
docker-compose exec php-8.3-apache whoami

# Verificar permissões
docker-compose exec php-8.3-apache ls -la ./cache/

# Se necessário, ajustar permissões (dentro do container)
docker-compose exec php-8.3-apache chmod -R 777 ./cache/
```

## 📝 Códigos de Saída

| Código | Significado |
|--------|-------------|
| `0` | ✅ Sucesso (arquivo/diretório removido ou não existia) |
| `1` | ❌ Erro (falha ao remover ou caminho inválido) |

### Verificar Código de Saída

```bash
# Linux/Mac
php scripts/developer/secure_eraser_cli.php --dest=./cache/
echo $?  # Mostra 0 (sucesso) ou 1 (erro)

# Windows PowerShell
php scripts/developer/secure_eraser_cli.php --dest=./cache/
echo $LASTEXITCODE  # Mostra 0 (sucesso) ou 1 (erro)

# Composer
composer clear:cache
echo $?  # Verifica se o comando foi bem-sucedido
```

## 🔒 Segurança

### Proteções Implementadas

1. ✅ **Validação de caminho**: Verifica se é arquivo ou diretório válido antes de remover
2. ✅ **Try/Catch**: Captura exceções e evita crashes
3. ✅ **Operador `@`**: Suprime warnings para operações que podem falhar silenciosamente
4. ✅ **SKIP_DOTS**: Evita remover `.` e `..` acidentalmente
5. ✅ **Verificação de leitura**: Só remove se o arquivo/diretório for legível

### Limitações

- ⚠️ **Não verifica permissões** antes de tentar remover (depende do sistema operacional)
- ⚠️ **Não confirma remoção** (execução direta, sem prompt de confirmação)
- ⚠️ **Não faz backup** automático (removido é removido permanentemente)

### Recomendações

- 🔐 Use com **cuidado** em produção
- 🔐 Teste primeiro em ambiente de desenvolvimento
- 🔐 Considere fazer **backup** antes de remover diretórios grandes
- 🔐 Use **Git** para versionar arquivos importantes (não remova `.git/`)

## 🆚 Comparação com Alternativas

### vs. Comandos Sistema (exec/system)

| Aspecto | secure_eraser_cli.php | exec('rm -rf') |
|---------|----------------------|----------------|
| Cross-platform | ✅ Sim | ❌ Não (Linux/Mac only) |
| Segurança | ✅ Valida caminhos | ⚠️ Executa comando direto |
| Dependências | ✅ PHP puro | ❌ Requer comandos do sistema |
| Erros | ✅ Tratamento robusto | ⚠️ Depende do comando |
| Portabilidade | ✅ Funciona em qualquer PHP | ❌ Depende do OS |

### vs. PHP Inline no composer.json

| Aspecto | secure_eraser_cli.php | PHP inline |
|---------|----------------------|------------|
| Legibilidade | ✅ Código organizado | ❌ Difícil de ler |
| Manutenção | ✅ Fácil de ajustar | ❌ Difícil de editar |
| Reutilização | ✅ Pode ser usado em outros scripts | ❌ Só funciona no composer.json |
| Debugging | ✅ Mais fácil debugar | ❌ Difícil debugar |
| Erros | ✅ Mensagens claras | ❌ Erros de sintaxe confusos |

## 📚 Referências

- [PHP RecursiveIteratorIterator](https://www.php.net/manual/en/class.recursiveiteratoriterator.php)
- [PHP RecursiveDirectoryIterator](https://www.php.net/manual/en/class.recursivedirectoryiterator.php)
- [PHP unlink()](https://www.php.net/manual/en/function.unlink.php)
- [PHP rmdir()](https://www.php.net/manual/en/function.rmdir.php)
