# Solução para Problemas de Docker e Composer

## Problemas Resolvidos

### 1. Problema de Composer: `squizlabs/php_codesniffer`

**Problema**: A versão `4.0.x-dev` (exact version match) não estava disponível no repositório.

**Solução**: Alterado `composer.json` para aceitar versão mais flexível:
```json
"squizlabs/php_codesniffer": "^4.0@dev"
```

Isso permite instalar qualquer versão 4.x (incluindo versões estáveis e de desenvolvimento).

### 2. Problema de Variáveis de Ambiente no Apache

**Problema**: Apache não conseguia acessar as variáveis de ambiente (MYSQL_HOST, PGSQL_HOST, etc.), resultando em avisos:
```
AH00111: Config variable ${MYSQL_HOST} is not defined
```

**Solução Implementada**:

#### a) Arquivo `.env.example`
- Já existia com todas as variáveis de configuração
- Copie para `.env` e configure conforme seu ambiente
- O Docker Compose carrega automaticamente via `env_file: .env`

#### b) Script `docker-entrypoint.sh`
Modificado para:
1. Carregar variáveis do arquivo `.env` (ou `.env.example` como fallback)
2. Gerar arquivo `/etc/apache2/conf-available/env-vars.conf` dinamicamente
3. Habilitar o arquivo de configuração com `a2enconf env-vars`
4. Usar `PassEnv` para passar variáveis ao Apache

#### c) Arquivos de Configuração Apache
Atualizados (default.conf, my-site.conf, default-ssl.conf) para incluir:
```apache
# Incluir arquivo de variáveis de ambiente
Include /etc/apache2/conf-enabled/env-vars.conf
```

## Como Usar

### 1. Setup Inicial

```bash
# Copiar arquivo de exemplo
cp .env.example .env

# Editar arquivo .env com suas configurações
nano .env  # ou use seu editor preferido
```

### 2. Build e Iniciar Container

```bash
# Windows
.\setup.bat --build-arg PHP_VERSION=8.1 --run "docker compose up -d apache"

# Linux/Mac
./setup.sh --build-arg PHP_VERSION=8.1 --run "docker compose up -d apache"
```

### 3. Instalar Dependências

```bash
docker exec php-8.1-apache composer install
```

### 4. Verificar Variáveis no Container

```bash
# Verificar se o arquivo foi gerado
docker exec php-8.1-apache cat /etc/apache2/conf-enabled/env-vars.conf

# Verificar logs do entrypoint
docker logs php-8.1-apache 2>&1 | grep "docker-entrypoint"
```

### 5. Acessar as Variáveis em PHP

```php
<?php
$mysql_host = getenv('MYSQL_HOST');
$mysql_port = getenv('MYSQL_PORT') ?: 3306;
$mysql_db = getenv('MYSQL_DATABASE');
// ... etc
?>
```

## Variáveis Disponíveis

Veja a documentação completa em [docker/ENVIRONMENT_SETUP.md](docker/ENVIRONMENT_SETUP.md)

Principais variáveis:
- MySQL: `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USERNAME`, `MYSQL_PASSWORD`, `MYSQL_CHARSET`
- PostgreSQL: `PGSQL_HOST`, `PGSQL_PORT`, `PGSQL_DATABASE`, `PGSQL_USERNAME`, `PGSQL_PASSWORD`, `PGSQL_CHARSET`
- SQL Server: `SQLSRV_HOST`, `SQLSRV_PORT`, `SQLSRV_DATABASE`, `SQLSRV_USERNAME`, `SQLSRV_PASSWORD`, `SQLSRV_CHARSET`
- Oracle: `OCI_HOST`, `OCI_PORT`, `OCI_DATABASE`, `OCI_USERNAME`, `OCI_PASSWORD`, `OCI_CHARSET`
- Firebird: `FBIRD_HOST`, `FBIRD_PORT`, `FBIRD_DATABASE`, `FBIRD_USERNAME`, `FBIRD_PASSWORD`, `FBIRD_CHARSET`
- SQLite: `SQLITE_DATABASE`, `SQLITE_DATABASE_MEMORY`, `SQLITE_CHARSET`

## Troubleshooting

### Variáveis ainda aparecem não definidas

1. Reconstrua a imagem:
```bash
docker compose down
docker compose build --no-cache php-8.1-apache
docker compose up -d apache
```

2. Verifique o arquivo .env:
```bash
docker exec php-8.1-apache cat /app/.env
```

3. Verifique se o arquivo env-vars.conf foi criado:
```bash
docker exec php-8.1-apache ls -la /etc/apache2/conf-enabled/env-vars.conf
```

### Composer ainda com erro de versão

Limpe o cache do composer:
```bash
docker exec php-8.1-apache composer clear-cache
docker exec php-8.1-apache composer install
```

## Segurança

⚠️ **IMPORTANTE**: 
- Nunca commite o arquivo `.env` no Git
- Adicione `/.env` ao `.gitignore`
- Use o `.env.example` como template para novas configurações
