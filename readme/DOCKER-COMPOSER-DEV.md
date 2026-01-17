# Docker - Controle de Dependências Dev/Prod

## 📋 Visão Geral

Os Dockerfiles agora suportam um **build argument** `COMPOSER_NO_DEV` que permite escolher entre instalar dependências apenas de produção ou incluir dependências de desenvolvimento.

## 🎯 Como Funciona

### Build Argument: `COMPOSER_NO_DEV`

- **Padrão**: `false` (instala **todas** as dependências, incluindo dev)
- **Produção**: `true` (instala **apenas** dependências de produção)

### Comportamento

| `COMPOSER_NO_DEV` | Comando Executado | Dependências Instaladas |
|-------------------|-------------------|-------------------------|
| `false` (padrão) | `composer install -n --ignore-platform-reqs` | ✅ Todas (prod + dev) |
| `true` | `composer install -n --ignore-platform-reqs --no-dev` | ✅ Apenas produção |

## 🚀 Uso

### Build para Desenvolvimento (Padrão)

```bash
# Sem especificar (padrão: false = instala dev)
docker-compose build php-8.3-apache

# Ou explicitamente
docker-compose build --build-arg COMPOSER_NO_DEV=false php-8.3-apache
```

**Resultado**: Instala todas as dependências, incluindo:
- `code-lts/doctum` (geração de docs)
- `phpunit/phpunit` (testes)
- `phpstan/phpstan` (análise estática)
- `squizlabs/php_codesniffer` (linting)
- E todas as outras dependências em `require-dev`

### Build para Produção

```bash
# Instalar apenas dependências de produção
docker-compose build --build-arg COMPOSER_NO_DEV=true php-8.3-apache

# Ou usando docker build diretamente
docker build \
  --build-arg PHP_VERSION=8.3 \
  --build-arg COMPOSER_NO_DEV=true \
  -t php-8.3-apache:prod \
  -f docker/stack-apache/php-apache/Dockerfile .
```

**Resultado**: Instala apenas dependências de produção:
- ❌ **NÃO** instala dependências de `require-dev`
- ✅ Instala apenas dependências de `require`
- 🎯 Imagem menor e mais segura para produção

## 📝 Exemplos Práticos

### Exemplo 1: Desenvolvimento Local

```bash
# Build normal (inclui dev dependencies)
docker-compose build php-8.3-apache

# Executar scripts de dev
docker-compose exec php-8.3-apache composer docs
docker-compose exec php-8.3-apache composer test
docker-compose exec php-8.3-apache composer lint
```

### Exemplo 2: CI/CD Pipeline (Dev)

```yaml
# .github/workflows/ci.yml ou .gitlab-ci.yml
build-dev:
  script:
    - docker-compose build --build-arg COMPOSER_NO_DEV=false php-8.3-apache
    - docker-compose up -d php-8.3-apache
    - docker-compose exec php-8.3-apache composer test
    - docker-compose exec php-8.3-apache composer lint
```

### Exemplo 3: CI/CD Pipeline (Produção)

```yaml
build-prod:
  script:
    - docker-compose build --build-arg COMPOSER_NO_DEV=true php-8.3-apache
    - docker tag php-8.3-apache:latest registry.example.com/php-8.3-apache:prod
    - docker push registry.example.com/php-8.3-apache:prod
```

### Exemplo 4: docker-compose.yml

```yaml
services:
  php-8.3-apache-dev:
    build:
      context: .
      dockerfile: docker/stack-apache/php-apache/Dockerfile
      args:
        PHP_VERSION: "8.3"
        COMPOSER_NO_DEV: "false"  # Instala dev dependencies
    # ...

  php-8.3-apache-prod:
    build:
      context: .
      dockerfile: docker/stack-apache/php-apache/Dockerfile
      args:
        PHP_VERSION: "8.3"
        COMPOSER_NO_DEV: "true"   # Apenas production dependencies
    # ...
```

## 🔍 Verificar Dependências Instaladas

### Dentro do Container

```bash
# Verificar se phpunit está instalado (dev dependency)
docker-compose exec php-8.3-apache php vendor/bin/phpunit --version

# Se COMPOSER_NO_DEV=true: ❌ comando não encontrado
# Se COMPOSER_NO_DEV=false: ✅ versão exibida

# Verificar se doctum está instalado (dev dependency)
docker-compose exec php-8.3-apache php vendor/bin/doctum.php --version

# Listar todos os pacotes instalados
docker-compose exec php-8.3-apache composer show
```

## ⚠️ Importante

### Scripts Composer

Alguns scripts do `composer.json` dependem de ferramentas de dev:

- ✅ **Sempre disponíveis** (não dependem de dev):
  - `composer clear:cache`
  - `composer clear:docs`
  - `composer run:env`
  - `composer clear:*` (todos usam PHP inline)

- ⚠️ **Requerem dev dependencies**:
  - `composer docs` → requer `code-lts/doctum`
  - `composer test` → requer `phpunit/phpunit`
  - `composer lint` → requer `phpstan`, `phpcs`, etc.

### Recomendação

- **Desenvolvimento**: Use `COMPOSER_NO_DEV=false` (padrão)
- **Produção**: Use `COMPOSER_NO_DEV=true` (não precisa de ferramentas de dev)

## 🐳 Dockerfiles Suportados

Esta funcionalidade está disponível em **todos** os Dockerfiles:

1. ✅ `docker/stack-apache/php-apache/Dockerfile`
2. ✅ `docker/stack-nginx/php-fpm/Dockerfile`
3. ✅ `docker/stack-frankenphp/php-frankenphp/Dockerfile`

## 📚 Referências

- [Composer Documentation - require vs require-dev](https://getcomposer.org/doc/01-basic-usage.md#the-require-key)
- [Docker Build Arguments](https://docs.docker.com/engine/reference/builder/#arg)
- [Docker Compose Build Args](https://docs.docker.com/compose/compose-file/build/#args)
