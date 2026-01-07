# Stack FrankenPHP

Este diretório contém os containers para a stack FrankenPHP.

## Estrutura

- `php-frankenphp/` - Containers PHP com FrankenPHP integrado (Caddy + PHP)

## Como funciona

O FrankenPHP é um servidor de aplicação PHP moderno construído sobre o servidor web Caddy. Ele integra PHP diretamente no Caddy, oferecendo:

- **Performance Superior**: Worker mode com threads, menos overhead
- **Early Hints**: Suporte a HTTP/103 para melhor performance
- **Hot Reload**: Recarregamento automático durante desenvolvimento
- **HTTP/2 e HTTP/3**: Suporte nativo a protocolos modernos
- **Arquitetura Simples**: Um único container (não precisa de servidor web separado)

## Limitações

⚠️ **IMPORTANTE**: FrankenPHP suporta apenas PHP 8.2, 8.3, 8.4 e 8.5.

- ❌ PHP 8.0 e 8.1 **NÃO são suportados**
- ✅ Use Apache ou Nginx+PHP-FPM para PHP 8.0 e 8.1

## Requisitos ZTS

FrankenPHP usa PHP compilado com ZTS (Zend Thread Safety), o que significa:

- Todas as extensões PHP devem ser compatíveis com ZTS
- Algumas extensões podem requerer compilação manual
- Extensões não thread-safe não funcionarão (ex: `imap`, `newrelic`)

## Uso

Verifique o `docker-compose.yml` para ver como os serviços são configurados e iniciados.

### Exemplo de uso individual

```bash
# Windows
.\setup.bat --build-arg PHP_VERSION=8.3 --run "docker compose up -d frankenphp"

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.3 --run "docker compose up -d frankenphp"
```

O `setup.bat`/`setup.sh` automaticamente converte `frankenphp` para `php-{PHP_VERSION}-frankenphp`.

## Referências

- [FrankenPHP Documentation](https://frankenphp.dev/)
- [FrankenPHP GitHub](https://github.com/dunglas/frankenphp)
- [Caddy Web Server](https://caddyserver.com/)
