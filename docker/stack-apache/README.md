# Stack Apache

Este diretório contém os containers para a stack Apache standalone (PHP integrado).

## Estrutura

- `php-apache/` - Containers PHP com Apache integrado (mod_php)

## Como funciona

O Apache é executado com módulo PHP integrado (mod_php), processando diretamente requisições PHP sem necessidade de um processador separado. Esta arquitetura oferece:

- **Arquitetura Monolítica**: Apache e PHP no mesmo container, facilitando deployment
- **Processamento Integrado**: mod_php processa PHP diretamente no processo Apache
- **Compatibilidade Ampla**: Suporta todas as versões PHP de 8.0 até 8.5
- **Módulos Apache**: Inclui rewrite, SSL, headers, autoindex e outros módulos essenciais
- **Configuração Flexível**: Suporta múltiplos virtual hosts (HTTP e HTTPS)
- **SSL/TLS**: Certificados auto-assinados configurados para desenvolvimento
- **Directory Listing**: Visualização customizada de diretórios com tema próprio

## Versões Suportadas

✅ **Todas as versões PHP são suportadas**: 8.0, 8.1, 8.2, 8.3, 8.4 e 8.5

- PHP 8.0: Usa imagem base sem sufixo `-bookworm`
- PHP 8.1+: Usa imagem base com sufixo `-bookworm` para melhor compatibilidade

## Características

### Módulos Apache Habilitados

- `mod_rewrite` - Suporte a URLs amigáveis e redirecionamentos
- `mod_ssl` - Suporte a HTTPS/SSL
- `mod_headers` - Manipulação de cabeçalhos HTTP
- `mod_autoindex` - Listagem de diretórios customizada

### Extensões PHP Incluídas

- Core: `simplexml`, `iconv`, `zlib`, `pdo`, `pdo_sqlite`, `sqlite3`
- MySQL: `pdo_mysql`, `mysqli`
- PostgreSQL: `pdo_pgsql`, `pgsql`
- SQL Server: `sqlsrv`, `pdo_sqlsrv`
- Oracle: `oci8`, `pdo_oci`
- Firebird: `pdo_firebird`
- ODBC: `odbc`, `pdo_odbc`
- Desenvolvimento: `xdebug`, `yaml`, `pcov`, `mcrypt`

## Uso

Verifique o `docker-compose.yml` para ver como os serviços são configurados e iniciados.

### Exemplo de uso individual

```bash
# Windows
.\setup.bat --build-arg PHP_VERSION=8.3 --run "docker compose up -d apache"
# Comando executado: docker compose up -d php-8.3-apache

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.3 --run "docker compose up -d apache"
# Comando executado: docker compose up -d php-8.3-apache
```

### Exemplo de uso em massa

```bash
# Windows - Instala todas as versões (8.0 a 8.5)
.\setupAll.bat --apache

# Linux/MacOS - Instala todas as versões (8.0 a 8.5)
./setupAll.sh --apache
```

O `setup.bat`/`setup.sh` automaticamente converte `apache` para `php-{PHP_VERSION}-apache`.

## Considerações

### Vantagens

- ✅ Simplicidade: Um único container para tudo
- ✅ Compatibilidade: Funciona com todas as versões PHP suportadas
- ✅ .htaccess: Suporte completo a arquivos .htaccess
- ✅ Módulos: Fácil habilitação de módulos Apache adicionais

### Desvantagens

- ⚠️ Performance: Pode ser menos eficiente que PHP-FPM para cargas altas
- ⚠️ Escalabilidade: Cada processo Apache carrega PHP, consumindo mais memória
- ⚠️ Flexibilidade: Menos flexível que arquiteturas separadas (Nginx+PHP-FPM)

## Referências

- [Apache HTTP Server](https://httpd.apache.org/)
- [PHP Apache Documentation](https://www.php.net/manual/en/install.unix.apache2.php)
- [mod_php Documentation](https://httpd.apache.org/docs/current/mod/mod_php.html)
