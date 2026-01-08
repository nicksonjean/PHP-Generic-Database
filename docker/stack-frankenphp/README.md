# Stack FrankenPHP

Este diretório contém os containers para a stack FrankenPHP.

## Estrutura

- `php-frankenphp/` - Containers PHP com FrankenPHP integrado (Caddy + PHP)

## Como funciona

O FrankenPHP é um servidor de aplicação PHP moderno construído sobre o servidor web Caddy. Ele integra PHP diretamente no Caddy, oferecendo:

- **Arquitetura Monolítica**: Caddy e PHP no mesmo container, facilitando deployment
- **Worker Mode**: Processamento com threads para melhor performance
- **Performance Superior**: Menos overhead comparado a arquiteturas separadas
- **Early Hints**: Suporte a HTTP/103 para melhor performance (preload de recursos)
- **Hot Reload**: Recarregamento automático durante desenvolvimento
- **HTTP/2 e HTTP/3**: Suporte nativo a protocolos modernos
- **SSL/TLS Automático**: Certificados auto-assinados configurados para desenvolvimento
- **Directory Listing**: Visualização customizada de diretórios com template próprio
- **Compressão**: Suporte a gzip e zstd para arquivos estáticos
- **Segurança**: Headers de segurança configurados automaticamente

## Versões Suportadas

⚠️ **IMPORTANTE**: FrankenPHP suporta apenas PHP 8.2, 8.3, 8.4 e 8.5.

- ✅ PHP 8.2, 8.3, 8.4 e 8.5: Totalmente suportados
- ❌ PHP 8.0 e 8.1 **NÃO são suportados**
- ✅ Use Apache ou Nginx+PHP-FPM para PHP 8.0 e 8.1

Todas as versões suportadas usam imagem base com sufixo `-bookworm` (Debian 12).

## Requisitos ZTS

FrankenPHP usa PHP compilado com ZTS (Zend Thread Safety), o que significa:

- Todas as extensões PHP devem ser compatíveis com ZTS
- Extensões são instaladas via `install-php-extensions` que suporta ZTS
- Algumas extensões podem requerer compilação manual (ex: `sqlsrv` para PHP 8.5)
- Extensões não thread-safe não funcionarão (ex: `imap`, `newrelic`)

## Características

### Recursos do Caddy/FrankenPHP

- **Worker Mode**: Execução PHP com threads para melhor throughput
- **Auto HTTPS**: Configuração automática de certificados SSL (usando `internal` para dev)
- **HTTP/2 e HTTP/3**: Suporte nativo a protocolos HTTP modernos
- **Compressão**: Gzip e zstd para otimização de arquivos estáticos
- **File Server**: Servidor de arquivos estáticos integrado
- **Template Customizado**: Directory listing com template próprio para filtrar arquivos
- **Security Headers**: Headers de segurança configurados (X-Content-Type-Options, X-Frame-Options, etc.)
- **Access Control**: Bloqueio automático de arquivos ocultos e sensíveis

### Extensões PHP Incluídas

Todas as extensões são instaladas via `install-php-extensions` que suporta ZTS:

- Core: `simplexml`, `iconv`, `zlib`, `pdo`, `pdo_sqlite`, `sqlite3`
- MySQL: `pdo_mysql`, `mysqli`
- PostgreSQL: `pdo_pgsql`, `pgsql`
- SQL Server: `sqlsrv`, `pdo_sqlsrv` (com verificação ZTS para PHP 8.2-8.4, compilação manual para 8.5)
- Oracle: `oci8`, `pdo_oci` (com verificação ZTS)
- Firebird: `pdo_firebird`
- ODBC: `odbc`, `pdo_odbc` (com drivers configurados)
- Desenvolvimento: `xdebug`, `yaml`, `pcov`

## Uso

Verifique o `docker-compose.yml` para ver como os serviços são configurados e iniciados.

### Exemplo de uso individual

```bash
# Windows
.\setup.bat --build-arg PHP_VERSION=8.3 --run "docker compose up -d frankenphp"
# Comando executado: docker compose up -d php-8.3-frankenphp

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.3 --run "docker compose up -d frankenphp"
# Comando executado: docker compose up -d php-8.3-frankenphp
```

O `setup.bat`/`setup.sh` automaticamente converte `frankenphp` para `php-{PHP_VERSION}-frankenphp`.

### Exemplo de uso em massa

```bash
# Windows - Instala todas as versões suportadas (8.2 a 8.5)
.\setupAll.bat --frankenphp

# Linux/MacOS - Instala todas as versões suportadas (8.2 a 8.5)
./setupAll.sh --frankenphp
```

## Considerações

### Vantagens

- ✅ Performance: Worker mode com threads oferece melhor performance
- ✅ Simplicidade: Um único container para tudo (Caddy + PHP)
- ✅ Moderno: Suporte nativo a HTTP/2, HTTP/3 e Early Hints
- ✅ Auto HTTPS: Configuração automática de certificados SSL
- ✅ Hot Reload: Recarregamento automático durante desenvolvimento
- ✅ Eficiência: Menos overhead comparado a arquiteturas separadas
- ✅ Produção: Cada vez mais usado em ambientes de produção modernos

### Desvantagens

- ⚠️ Limitações de Versão: Apenas PHP 8.2+ (não funciona com 8.0 e 8.1)
- ⚠️ ZTS: Todas as extensões devem ser compatíveis com ZTS
- ⚠️ Compatibilidade: Algumas extensões podem não funcionar (extensões não thread-safe)
- ⚠️ Madurez: Tecnologia mais recente, menos madura que Apache/Nginx tradicional
- ⚠️ Compilação: Algumas extensões podem requerer compilação manual para versões mais novas (ex: `sqlsrv` para PHP 8.5)

## Referências

- [FrankenPHP Documentation](https://frankenphp.dev/)
- [FrankenPHP GitHub](https://github.com/dunglas/frankenphp)
- [Caddy Web Server](https://caddyserver.com/)
- [Caddyfile Documentation](https://caddyserver.com/docs/caddyfile)
