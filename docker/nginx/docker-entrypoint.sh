#!/bin/sh
set -e

# Substituir o nome do serviço PHP na configuração do Nginx
PHP_SERVICE=${PHP_SERVICE:-app}

# Substituir 'php:9000' pelo nome do serviço PHP correto
# No Alpine, os arquivos de configuração estão em http.d, não em conf.d
sed -i "s/fastcgi_pass php:9000/fastcgi_pass ${PHP_SERVICE}:9000/g" /etc/nginx/http.d/default.conf 2>/dev/null || true
sed -i "s/fastcgi_pass php:9000/fastcgi_pass ${PHP_SERVICE}:9000/g" /etc/nginx/http.d/default-ssl.conf 2>/dev/null || true

# Executar o comando padrão do Nginx
exec "$@"
