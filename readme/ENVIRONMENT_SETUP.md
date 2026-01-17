# Setup de Variáveis de Ambiente para Docker

## Instruções

### 1. Criar arquivo `.env` local

Na raiz do projeto, copie o arquivo `.env.example` para `.env`:

```bash
# No Windows
copy .env.example .env

# No Linux/Mac
cp .env.example .env
```

### 2. Configurar variáveis de ambiente

Edite o arquivo `.env` criado e configure as variáveis de acordo com seu ambiente:

```dotenv
# MySQL Configuration
MYSQL_HOST=mysql
MYSQL_PORT=3306
MYSQL_DATABASE=demodev
MYSQL_USERNAME=root
MYSQL_PASSWORD=masterkey

# PostgreSQL Configuration
PGSQL_HOST=postgres
PGSQL_PORT=5432
PGSQL_DATABASE=demodev
PGSQL_USERNAME=postgres
PGSQL_PASSWORD=masterkey

# SQL Server Configuration
SQLSRV_HOST=sqlsrv
SQLSRV_PORT=1433
SQLSRV_DATABASE=demodev
SQLSRV_USERNAME=sa
SQLSRV_PASSWORD=Masterkey@1

# Oracle Configuration
OCI_HOST=oracle
OCI_PORT=1521
OCI_DATABASE=freepdb1
OCI_USERNAME=hr
OCI_PASSWORD=masterkey

# Firebird Configuration
FBIRD_HOST=firebird
FBIRD_PORT=3050
FBIRD_DATABASE=/firebird/data/DB.FDB
FBIRD_USERNAME=sysdba
FBIRD_PASSWORD=masterkey

# SQLite Configuration
SQLITE_DATABASE=../../resources/database/sqlite/data/DB.SQLITE
SQLITE_DATABASE_MEMORY=memory
```

### 3. Build e iniciar o container

Execute o comando de setup do projeto:

```bash
# Windows
.\setup.bat --build-arg PHP_VERSION=8.1 --run "docker compose up -d apache"

# Linux/Mac
./setup.sh --build-arg PHP_VERSION=8.1 --run "docker compose up -d apache"
```

### 4. Verificar variáveis de ambiente

As variáveis de ambiente serão automaticamente:
1. Carregadas do arquivo `.env` no container
2. Exportadas para o Apache via `PassEnv` no arquivo de configuração `/etc/apache2/conf-available/env-vars.conf`
3. Disponíveis para scripts PHP e páginas via `$_ENV` ou `getenv()`

### Acessar as variáveis em PHP

```php
<?php
// Acessar variáveis de ambiente no código PHP
$mysql_host = getenv('MYSQL_HOST');
$mysql_port = getenv('MYSQL_PORT');
$mysql_database = getenv('MYSQL_DATABASE');
$mysql_username = getenv('MYSQL_USERNAME');
$mysql_password = getenv('MYSQL_PASSWORD');

// Ou através do array $_ENV
$mysql_host = $_ENV['MYSQL_HOST'] ?? 'localhost';
?>
```

## Variáveis Suportadas

### MySQL / MySQLi
- `MYSQL_HOST` - Host do servidor MySQL
- `MYSQL_PORT` - Porta do servidor MySQL (padrão: 3306)
- `MYSQL_DATABASE` - Nome do banco de dados
- `MYSQL_USERNAME` - Usuário
- `MYSQL_PASSWORD` - Senha
- `MYSQL_CHARSET` - Charset (padrão: utf8)
- `MYSQL_ODBC_DRIVER` - Driver ODBC para MySQL

### PostgreSQL
- `PGSQL_HOST` - Host do servidor PostgreSQL
- `PGSQL_PORT` - Porta do servidor PostgreSQL (padrão: 5432)
- `PGSQL_DATABASE` - Nome do banco de dados
- `PGSQL_USERNAME` - Usuário
- `PGSQL_PASSWORD` - Senha
- `PGSQL_CHARSET` - Charset (padrão: UTF8)

### SQL Server
- `SQLSRV_HOST` - Host do servidor SQL Server
- `SQLSRV_PORT` - Porta do servidor SQL Server (padrão: 1433)
- `SQLSRV_DATABASE` - Nome do banco de dados
- `SQLSRV_USERNAME` - Usuário
- `SQLSRV_PASSWORD` - Senha
- `SQLSRV_CHARSET` - Charset (padrão: UTF-8)

### Oracle
- `OCI_HOST` - Host do servidor Oracle
- `OCI_PORT` - Porta do servidor Oracle (padrão: 1521)
- `OCI_DATABASE` - Nome do banco de dados (SID ou Service Name)
- `OCI_USERNAME` - Usuário
- `OCI_PASSWORD` - Senha
- `OCI_CHARSET` - Charset (padrão: UTF8)

### Firebird / InterBase
- `FBIRD_HOST` - Host do servidor Firebird
- `FBIRD_PORT` - Porta do servidor Firebird (padrão: 3050)
- `FBIRD_DATABASE` - Caminho para o arquivo de banco de dados
- `FBIRD_USERNAME` - Usuário
- `FBIRD_PASSWORD` - Senha
- `FBIRD_CHARSET` - Charset (padrão: UTF8)

### SQLite
- `SQLITE_DATABASE` - Caminho para o arquivo de banco de dados SQLite
- `SQLITE_DATABASE_MEMORY` - Banco de dados em memória (:memory:)
- `SQLITE_CHARSET` - Charset (padrão: UTF8)

### ODBC
- `ODBC_DSN_NAME` - Nome da fonte de dados ODBC
- `ODBC_DRIVER` - Driver ODBC
- `ODBC_USERNAME` - Usuário ODBC
- `ODBC_PASSWORD` - Senha ODBC

### Outros
- `IBASE_HOST` - Host InterBase (compatibilidade)
- `IBASE_PORT` - Porta InterBase
- `IBASE_DATABASE` - Banco de dados InterBase
- `IBASE_USERNAME` - Usuário InterBase
- `IBASE_PASSWORD` - Senha InterBase
- `IBASE_CHARSET` - Charset InterBase

## Solução de Problemas

### Variáveis de ambiente não aparecem em PHP

1. Verifique se o arquivo `.env` existe na raiz do projeto
2. Verifique se as variáveis estão no formato correto (SEM espaços ao redor do `=`)
3. Reconstrua a imagem Docker:

```bash
docker compose down
docker compose build --no-cache php-8.1-apache
docker compose up -d apache
```

### Erro: "Config variable ${VARIABLE_NAME} is not defined"

Isso significa que a variável de ambiente não foi exportada corretamente para o Apache.

1. Verifique o arquivo `/etc/apache2/conf-enabled/env-vars.conf` dentro do container:

```bash
docker exec -it php-8.1-apache cat /etc/apache2/conf-enabled/env-vars.conf
```

2. Verifique se o arquivo foi gerado corretamente no logs:

```bash
docker logs php-8.1-apache 2>&1 | grep "docker-entrypoint"
```

3. Se necessário, reinicie o container:

```bash
docker compose restart apache
```

## Segurança

⚠️ **IMPORTANTE**: Nunca commite o arquivo `.env` no repositório Git!

O arquivo `.env` contém informações sensíveis como senhas. Ele deve estar no `.gitignore`:

```bash
# .gitignore
.env
.env.local
```

Use o arquivo `.env.example` como referência e template para novas configurações.
