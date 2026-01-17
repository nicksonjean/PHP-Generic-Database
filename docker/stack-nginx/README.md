# Stack Nginx + PHP-FPM

Este diretório contém os containers para a stack Nginx + PHP-FPM.

## Estrutura

- `nginx/` - Container Alpine com Nginx (web server)
- `php-fpm/` - Containers PHP-FPM (processador PHP) que trabalham em conjunto com o Nginx

## Como funciona

O Nginx atua como servidor web reverso, processando requisições HTTP/HTTPS e encaminhando requisições PHP para os containers PHP-FPM via FastCGI (porta 9000). Esta arquitetura oferece:

- **Arquitetura Separada**: Nginx e PHP-FPM em containers distintos para melhor escalabilidade
- **Performance Superior**: PHP-FPM gerencia processos PHP de forma eficiente
- **Flexibilidade**: Possibilidade de escalar Nginx e PHP-FPM independentemente
- **FastCGI Protocol**: Comunicação eficiente entre Nginx e PHP-FPM
- **Modo Unificado**: Um único container Nginx servindo múltiplas versões PHP (8.0-8.5)
- **Modo Individual**: Containers Nginx separados para cada versão PHP
- **HTTP/2**: Suporte nativo a HTTP/2 para melhor performance
- **SSL/TLS**: Certificados auto-assinados configurados para desenvolvimento
- **Directory Listing**: Visualização customizada de diretórios com FancyIndex

## Versões Suportadas

✅ **Todas as versões PHP são suportadas**: 8.0, 8.1, 8.2, 8.3, 8.4 e 8.5

- PHP 8.0: Usa imagem base sem sufixo `-bookworm`
- PHP 8.1+: Usa imagem base com sufixo `-bookworm` para melhor compatibilidade

## Modos de Operação

### Modo Unificado (nginx-unified)

Um único container Nginx que serve todas as versões PHP simultaneamente:

- Portas HTTP: 8000, 8100, 8200, 8300, 8400, 8500
- Portas HTTPS: 8043, 8143, 8243, 8343, 8443, 8543
- Cada porta roteia para o PHP-FPM correspondente
- Usado pelo `setupAll.bat`/`setupAll.sh`

### Modo Individual (nginx-php-{VERSION})

Containers Nginx separados para cada versão PHP:

- Permite executar versões específicas independentemente
- Cada container tem suas próprias portas HTTP/HTTPS
- Usado pelo `setup.bat`/`setup.sh` para instalações individuais

## Características

### Recursos Nginx

- **FancyIndex**: Listagem de diretórios customizada e elegante
- **Cache de Arquivos Estáticos**: Cache otimizado para CSS, JS, imagens
- **Compressão**: Gzip para arquivos de texto
- **HTTP/2**: Suporte completo a HTTP/2
- **SSL/TLS**: Configuração SSL completa com certificados auto-assinados
- **Rate Limiting**: Proteção contra requisições excessivas
- **Access Control**: Controle de acesso a arquivos ocultos

### Extensões PHP Incluídas (PHP-FPM)

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
# Windows - Modo individual (Nginx + PHP-FPM específicos)
.\setup.bat --build-arg PHP_VERSION=8.3 --run "docker compose up -d nginx"
# Comando executado: docker compose up -d nginx-php-8.3 php-8.3-fpm

# Linux/MacOS - Modo individual
./setup.sh --build-arg PHP_VERSION=8.3 --run "docker compose up -d nginx"
# Comando executado: docker compose up -d nginx-php-8.3 php-8.3-fpm
```

O `setup.bat`/`setup.sh` automaticamente converte `nginx` para `nginx-php-{PHP_VERSION}` e inicia o PHP-FPM correspondente.

### Exemplo de uso em massa (modo unificado)

```bash
# Windows - Instala todas as versões com Nginx unificado
.\setupAll.bat
# ou explicitamente
.\setupAll.bat --nginx

# Linux/MacOS - Instala todas as versões com Nginx unificado
./setupAll.sh
# ou explicitamente
./setupAll.sh --nginx
```

## Considerações

### Vantagens

- ✅ Performance: PHP-FPM é otimizado para alto desempenho
- ✅ Escalabilidade: Possibilidade de escalar Nginx e PHP-FPM separadamente
- ✅ Eficiência: Nginx consome menos recursos para servir arquivos estáticos
- ✅ Flexibilidade: Fácil adicionar mais workers PHP-FPM sem reiniciar Nginx
- ✅ Produção: Arquitetura recomendada para ambientes de produção

### Desvantagens

- ⚠️ Complexidade: Requer gerenciar dois tipos de containers (Nginx + PHP-FPM)
- ⚠️ Configuração: Requer configuração adequada do FastCGI
- ⚠️ .htaccess: Não suporta arquivos .htaccess (precisa converter para configuração Nginx)

## Referências

- [Nginx Documentation](https://nginx.org/en/docs/)
- [PHP-FPM Documentation](https://www.php.net/manual/en/install.fpm.php)
- [FastCGI Protocol](https://fastcgi-archives.github.io/FastCGI_Specification.html)
- [Nginx PHP-FPM Configuration](https://www.nginx.com/resources/wiki/start/topics/examples/phpfcgi/)
