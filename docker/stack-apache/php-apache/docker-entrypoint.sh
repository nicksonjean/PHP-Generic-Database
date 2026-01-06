#!/bin/sh
set -e

# Script de entrada do Apache
# O Apache usa mod_php (PHP embutido), então não precisa de substituição de variáveis
# como o Nginx (que usa FastCGI com PHP-FPM)

# Atualizar cache de bibliotecas (equivalente ao que está na linha 549 do Dockerfile)
echo "[docker-entrypoint] Atualizando cache de bibliotecas..."
ldconfig

# Verificar se os arquivos de configuração existem
echo "[docker-entrypoint] Verificando arquivos de configuração do Apache..."
if [ ! -f /etc/apache2/sites-available/default.conf ]; then
    echo "[docker-entrypoint] AVISO: /etc/apache2/sites-available/default.conf não encontrado!"
    echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
    echo "[docker-entrypoint] Execute: docker compose build php-8.0-apache"
    echo "[docker-entrypoint] Arquivos disponíveis em /etc/apache2/sites-available/:"
    ls -la /etc/apache2/sites-available/ 2>/dev/null || true
    exit 1
fi

if [ ! -f /etc/apache2/sites-available/my-site.conf ]; then
    echo "[docker-entrypoint] AVISO: /etc/apache2/sites-available/my-site.conf não encontrado!"
    echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
    echo "[docker-entrypoint] Execute: docker compose build php-8.0-apache"
    echo "[docker-entrypoint] Arquivos disponíveis em /etc/apache2/sites-available/:"
    ls -la /etc/apache2/sites-available/ 2>/dev/null || true
    exit 1
fi

if [ ! -f /etc/apache2/sites-available/default-ssl.conf ]; then
    echo "[docker-entrypoint] AVISO: /etc/apache2/sites-available/default-ssl.conf não encontrado!"
    echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
    echo "[docker-entrypoint] Execute: docker compose build php-8.0-apache"
    echo "[docker-entrypoint] Arquivos disponíveis em /etc/apache2/sites-available/:"
    ls -la /etc/apache2/sites-available/ 2>/dev/null || true
    exit 1
fi

# Verificar se os sites estão habilitados
echo "[docker-entrypoint] Verificando sites habilitados..."
if [ ! -L /etc/apache2/sites-enabled/default.conf ]; then
    echo "[docker-entrypoint] Habilitando site default..."
    a2ensite default 2>/dev/null || true
fi

if [ ! -L /etc/apache2/sites-enabled/my-site.conf ]; then
    echo "[docker-entrypoint] Habilitando site my-site..."
    a2ensite my-site 2>/dev/null || true
fi

if [ ! -L /etc/apache2/sites-enabled/default-ssl.conf ]; then
    echo "[docker-entrypoint] Habilitando site default-ssl..."
    a2ensite default-ssl 2>/dev/null || true
fi

# Desabilitar site padrão do Apache se ainda estiver habilitado
if [ -L /etc/apache2/sites-enabled/000-default.conf ]; then
    echo "[docker-entrypoint] Desabilitando site padrão 000-default..."
    a2dissite 000-default 2>/dev/null || true
fi

# Verificar se os módulos necessários estão habilitados
echo "[docker-entrypoint] Verificando módulos do Apache..."
REQUIRED_MODS="headers rewrite ssl autoindex"
for mod in $REQUIRED_MODS; do
    if [ ! -L /etc/apache2/mods-enabled/${mod}.load ]; then
        echo "[docker-entrypoint] Habilitando módulo ${mod}..."
        a2enmod ${mod} 2>/dev/null || true
    fi
done

# Validar configuração do Apache antes de iniciar
echo "[docker-entrypoint] Testando configuração do Apache..."
apache2ctl configtest 2>&1 || {
    echo "[docker-entrypoint] ERRO na validação da configuração do Apache!"
    echo "[docker-entrypoint] Verificando arquivos de configuração:"
    ls -la /etc/apache2/sites-enabled/*.conf 2>/dev/null || true
    echo "[docker-entrypoint] Verificando logs de erro:"
    tail -20 /var/log/apache2/error.log 2>/dev/null || true
    exit 1
}

echo "[docker-entrypoint] Configuração do Apache validada com sucesso!"
echo "[docker-entrypoint] Sites habilitados:"
ls -la /etc/apache2/sites-enabled/*.conf 2>/dev/null || echo "Nenhum site habilitado"

# Se não houver argumentos ou se o argumento for apache2-foreground, iniciar Apache
if [ "$1" = 'apache2-foreground' ] || [ -z "$1" ]; then
    echo "[docker-entrypoint] Iniciando Apache..."
    exec apache2-foreground
else
    # Executar comando customizado
    exec "$@"
fi
