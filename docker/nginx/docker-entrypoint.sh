#!/bin/sh
set -e

# Script de entrada do Nginx
# As configurações unificadas (my-site.conf e default-ssl.conf) já possuem
# os nomes dos serviços PHP-FPM hardcoded (php-8.0-fpm, php-8.1-fpm, etc.)
# Para uso com setup.bat/setup.sh individual, o serviço nginx genérico
# usa default-simple.conf que precisa de substituição dinâmica da variável PHP_SERVICE

# Remover qualquer default.conf existente para evitar conflitos
rm -f /etc/nginx/http.d/default.conf 2>/dev/null || true

# Se PHP_SERVICE estiver definido, usar default-simple.conf (para serviço genérico)
# Caso contrário, usar my-site.conf (para nginx-unified)
if [ -n "$PHP_SERVICE" ]; then
    echo "[docker-entrypoint] Usando configuração genérica com PHP_SERVICE=$PHP_SERVICE"
    
    # Restaurar templates do backup se eles não existirem mas os backups existirem
    # Isso acontece quando o container é reiniciado (não recriado) após uma execução anterior
    if [ ! -f /etc/nginx/http.d/default-simple.conf ] && [ -f /etc/nginx/http.d/default-simple.conf.bak ]; then
        echo "[docker-entrypoint] Restaurando default-simple.conf do backup..."
        cp /etc/nginx/http.d/default-simple.conf.bak /etc/nginx/http.d/default-simple.conf 2>/dev/null || true
    fi
    if [ ! -f /etc/nginx/http.d/default-ssl-simple.conf ] && [ -f /etc/nginx/http.d/default-ssl-simple.conf.bak ]; then
        echo "[docker-entrypoint] Restaurando default-ssl-simple.conf do backup..."
        cp /etc/nginx/http.d/default-ssl-simple.conf.bak /etc/nginx/http.d/default-ssl-simple.conf 2>/dev/null || true
    fi
    
    # Verificar se o arquivo default-simple.conf existe
    if [ ! -f /etc/nginx/http.d/default-simple.conf ]; then
        echo "[docker-entrypoint] ERRO: /etc/nginx/http.d/default-simple.conf não encontrado!"
        echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
        echo "[docker-entrypoint] Execute: docker compose build nginx-php-8.0"
        echo "[docker-entrypoint] Arquivos disponíveis em /etc/nginx/http.d/:"
        ls -la /etc/nginx/http.d/ 2>/dev/null || true
        exit 1
    fi
    # Verificar se default.conf já existe e é válido (de um restart anterior)
    # Se existir e for válido, não precisamos recriar
    if [ -f /etc/nginx/http.d/default.conf ]; then
        echo "[docker-entrypoint] default.conf já existe, verificando se é válido..."
        # Verificar se o arquivo não está vazio e tem conteúdo válido (contém fastcgi_pass com o PHP_SERVICE correto)
        if [ -s /etc/nginx/http.d/default.conf ] && grep -q "fastcgi_pass.*${PHP_SERVICE}" /etc/nginx/http.d/default.conf 2>/dev/null; then
            echo "[docker-entrypoint] default.conf existente parece válido, reutilizando..."
            # Verificar se default-ssl.conf também existe e é válido
            if [ -f /etc/nginx/http.d/default-ssl.conf ] && grep -q "fastcgi_pass.*${PHP_SERVICE}" /etc/nginx/http.d/default-ssl.conf 2>/dev/null; then
                echo "[docker-entrypoint] default-ssl.conf existente também parece válido, reutilizando..."
                # Pular para validação final
                SKIP_GENERATION=true
            else
                echo "[docker-entrypoint] default-ssl.conf não existe ou é inválido, será recriado..."
                SKIP_GENERATION=false
            fi
        else
            echo "[docker-entrypoint] default.conf existente parece inválido ou não corresponde ao PHP_SERVICE atual, recriando..."
            rm -f /etc/nginx/http.d/default.conf 2>/dev/null || true
            rm -f /etc/nginx/http.d/default-ssl.conf 2>/dev/null || true
            SKIP_GENERATION=false
        fi
    else
        SKIP_GENERATION=false
    fi
    
    # IMPORTANTE: Remover arquivos conflitantes (my-site.conf) sempre, independente de reutilizar ou não
    # O nginx carrega TODOS os arquivos .conf do diretório http.d/
    # Se my-site.conf estiver presente, causará conflitos
    rm -f /etc/nginx/http.d/my-site.conf 2>/dev/null || true
    
    # Remover default.conf e default-ssl.conf apenas se vamos gerar novos
    if [ "$SKIP_GENERATION" != "true" ]; then
        rm -f /etc/nginx/http.d/default-ssl.conf 2>/dev/null || true
        rm -f /etc/nginx/http.d/default.conf 2>/dev/null || true
    fi
    
    # Substituir ${PHP_SERVICE} no default-simple.conf e usar como default.conf (HTTP)
    # Apenas se não estamos reutilizando configurações existentes
    if [ "$SKIP_GENERATION" != "true" ]; then
    # Usar sed para substituir ${PHP_SERVICE} de forma mais confiável
    # Isso evita problemas com envsubst e variáveis do nginx ($uri, $document_root, etc)
    echo "[docker-entrypoint] Substituindo \${PHP_SERVICE} por ${PHP_SERVICE} no default.conf (HTTP)..."
    
        # Ler o arquivo e fazer substituição usando uma abordagem mais simples e confiável
        # Usar cat + sed com escape correto
        cat /etc/nginx/http.d/default-simple.conf | \
            sed "s/\${PHP_SERVICE}/${PHP_SERVICE}/g" | \
            sed "s/\$PHP_SERVICE/${PHP_SERVICE}/g" > /etc/nginx/http.d/default.conf
        
        # Substituir ${PHP_SERVICE} no default-ssl-simple.conf e usar como default-ssl.conf (HTTPS)
        # NOTA: O nginx escuta na porta 443 dentro do container (porta padrão SSL)
        # O Docker Compose mapeia a porta externa (SSL_PORT do .env) para a porta 443 do container
        if [ -f /etc/nginx/http.d/default-ssl-simple.conf ]; then
            echo "[docker-entrypoint] Substituindo variáveis no default-ssl.conf (HTTPS)..."
            echo "[docker-entrypoint] Variáveis de ambiente disponíveis:"
            echo "[docker-entrypoint]   PHP_SERVICE=${PHP_SERVICE}"
            echo "[docker-entrypoint]   SSL_PORT=${SSL_PORT:-NÃO DEFINIDO} (porta externa mapeada para 443 no container)"
            
            echo "[docker-entrypoint] Conteúdo original do default-ssl-simple.conf (primeiras linhas):"
            head -5 /etc/nginx/http.d/default-ssl-simple.conf || true
            
            # Fazer substituição apenas do PHP_SERVICE
            # A porta SSL já está configurada como 443 no arquivo (porta padrão SSL dentro do container)
            cat /etc/nginx/http.d/default-ssl-simple.conf | \
                sed "s/\${PHP_SERVICE}/${PHP_SERVICE}/g" | \
                sed "s/\$PHP_SERVICE/${PHP_SERVICE}/g" > /etc/nginx/http.d/default-ssl.conf
            
            echo "[docker-entrypoint] Conteúdo gerado do default-ssl.conf (primeiras linhas):"
            head -5 /etc/nginx/http.d/default-ssl.conf || true
            
            # Verificar se a substituição SSL funcionou
            if grep -q '\${PHP_SERVICE}' /etc/nginx/http.d/default-ssl.conf; then
                echo "[docker-entrypoint] ERRO: Variável \${PHP_SERVICE} não foi substituída no default-ssl.conf!"
                exit 1
            fi
            if grep -q '\$PHP_SERVICE' /etc/nginx/http.d/default-ssl.conf; then
                echo "[docker-entrypoint] ERRO: Variável \$PHP_SERVICE não foi substituída no default-ssl.conf!"
                exit 1
            fi
            
            echo "[docker-entrypoint] Configuração SSL gerada com sucesso:"
            echo "[docker-entrypoint] Porta SSL configurada:"
            grep "listen.*ssl" /etc/nginx/http.d/default-ssl.conf || true
            echo "[docker-entrypoint] FastCGI configurado:"
            grep "fastcgi_pass" /etc/nginx/http.d/default-ssl.conf || true
        fi
        
        # IMPORTANTE: Criar backup dos templates antes de removê-los para restaurar em futuros restarts
        # Isso garante que os templates estarão disponíveis mesmo após reiniciar o container
        if [ -f /etc/nginx/http.d/default-simple.conf ]; then
            cp /etc/nginx/http.d/default-simple.conf /etc/nginx/http.d/default-simple.conf.bak 2>/dev/null || true
        fi
        if [ -f /etc/nginx/http.d/default-ssl-simple.conf ]; then
            cp /etc/nginx/http.d/default-ssl-simple.conf /etc/nginx/http.d/default-ssl-simple.conf.bak 2>/dev/null || true
        fi
        
        # Remover os arquivos template para evitar que o nginx tente carregá-los
        # Mas manteremos os backups para restaurar em futuros restarts
        rm -f /etc/nginx/http.d/default-ssl-simple.conf 2>/dev/null || true
        rm -f /etc/nginx/http.d/default-simple.conf 2>/dev/null || true
        
        # Verificar se a substituição funcionou (apenas se geramos novos arquivos)
        echo "[docker-entrypoint] Verificando substituição..."
        if grep -q '\${PHP_SERVICE}' /etc/nginx/http.d/default.conf; then
            echo "[docker-entrypoint] ERRO: Variável \${PHP_SERVICE} não foi substituída!"
            echo "[docker-entrypoint] Conteúdo do arquivo gerado:"
            cat /etc/nginx/http.d/default.conf
            exit 1
        fi
        if grep -q '\$PHP_SERVICE' /etc/nginx/http.d/default.conf; then
            echo "[docker-entrypoint] ERRO: Variável \$PHP_SERVICE não foi substituída!"
            echo "[docker-entrypoint] Conteúdo do arquivo gerado:"
            cat /etc/nginx/http.d/default.conf
            exit 1
        fi
        # Verificar se há referências problemáticas
        if grep -E '\$[a-zA-Z_]*[Ss]ervice' /etc/nginx/http.d/default.conf | grep -v "fastcgi_pass.*php-.*-fpm"; then
            echo "[docker-entrypoint] AVISO: Possíveis variáveis não substituídas encontradas:"
            grep -E '\$[a-zA-Z_]*[Ss]ervice' /etc/nginx/http.d/default.conf || true
        fi
        echo "[docker-entrypoint] Configuração gerada com sucesso:"
        grep "fastcgi_pass" /etc/nginx/http.d/default.conf || true
    else
        echo "[docker-entrypoint] Reutilizando configurações existentes (válidas)"
        # Remover templates se existirem (para evitar conflitos, mesmo quando reutilizamos)
        rm -f /etc/nginx/http.d/default-ssl-simple.conf 2>/dev/null || true
        rm -f /etc/nginx/http.d/default-simple.conf 2>/dev/null || true
    fi
    echo "[docker-entrypoint] Verificando se há outros arquivos .conf no diretório http.d:"
    ls -la /etc/nginx/http.d/*.conf 2>/dev/null || echo "Nenhum arquivo .conf encontrado"
    echo "[docker-entrypoint] Conteúdo completo do arquivo default.conf:"
    cat /etc/nginx/http.d/default.conf
    # Listar todos os arquivos .conf finais para debug
    echo "[docker-entrypoint] Arquivos .conf finais no diretório http.d:"
    ls -la /etc/nginx/http.d/*.conf 2>/dev/null || echo "Nenhum arquivo .conf encontrado"
    # Validar configuração antes de iniciar
    echo "[docker-entrypoint] Testando configuração do Nginx..."
    nginx -t 2>&1 || {
        echo "[docker-entrypoint] ERRO na validação da configuração do Nginx!"
        echo "[docker-entrypoint] Verificando arquivo nginx.conf principal:"
        grep -n "include" /etc/nginx/nginx.conf || true
        exit 1
    }
else
    echo "[docker-entrypoint] Usando configuração unificada (nginx-unified)"
    # Para nginx-unified, usar as configurações unificadas
    # IMPORTANTE: Remover arquivos de template que usam variáveis dinâmicas para evitar conflitos
    rm -f /etc/nginx/http.d/default-simple.conf 2>/dev/null || true
    rm -f /etc/nginx/http.d/default-ssl-simple.conf 2>/dev/null || true
    
    # Restaurar my-site.conf do backup se ele não existir mas o backup existir
    # Isso acontece quando o container é reiniciado (não recriado) após uma execução anterior
    if [ ! -f /etc/nginx/http.d/my-site.conf ] && [ -f /etc/nginx/http.d/my-site.conf.bak ]; then
        echo "[docker-entrypoint] Restaurando my-site.conf do backup..."
        mv /etc/nginx/http.d/my-site.conf.bak /etc/nginx/http.d/my-site.conf 2>/dev/null || true
    fi
    
    # Verificar se default.conf já existe e é válido (de uma execução anterior)
    # Se existir, não precisamos recriar a partir de my-site.conf
    if [ -f /etc/nginx/http.d/default.conf ]; then
        echo "[docker-entrypoint] default.conf já existe, verificando se é válido..."
        # Verificar se o arquivo não está vazio e tem conteúdo válido
        if [ -s /etc/nginx/http.d/default.conf ] && grep -q "server_name" /etc/nginx/http.d/default.conf 2>/dev/null; then
            echo "[docker-entrypoint] default.conf existente parece válido, reutilizando..."
        else
            echo "[docker-entrypoint] default.conf existente parece inválido, recriando..."
            rm -f /etc/nginx/http.d/default.conf 2>/dev/null || true
        fi
    fi
    
    # Se default.conf não existe, criar a partir de my-site.conf
    if [ ! -f /etc/nginx/http.d/default.conf ]; then
        # Verificar se my-site.conf existe antes de copiar
        echo "[docker-entrypoint] Verificando se my-site.conf existe..."
        if [ ! -f /etc/nginx/http.d/my-site.conf ]; then
            echo "[docker-entrypoint] ERRO: /etc/nginx/http.d/my-site.conf não encontrado!"
            echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
            echo "[docker-entrypoint] Execute: docker compose build nginx-unified"
            echo "[docker-entrypoint] Arquivos disponíveis em /etc/nginx/http.d/:"
            ls -la /etc/nginx/http.d/ 2>/dev/null || true
            exit 1
        fi
        
        echo "[docker-entrypoint] Copiando my-site.conf para default.conf..."
        cp /etc/nginx/http.d/my-site.conf /etc/nginx/http.d/default.conf
    fi
    
    # IMPORTANTE: NÃO remover my-site.conf - ele precisa estar disponível para reinícios futuros
    # O Nginx carrega todos os .conf, mas como my-site.conf e default.conf têm o mesmo conteúdo
    # após a cópia, não há problema em manter ambos. Alternativamente, podemos renomear.
    # Para evitar duplicação, vamos renomear my-site.conf para .bak após copiar
    # Mas apenas se ainda não foi renomeado (ou seja, se my-site.conf ainda existe)
    if [ -f /etc/nginx/http.d/my-site.conf ] && [ -f /etc/nginx/http.d/default.conf ]; then
        # Verificar se são iguais antes de renomear
        if cmp -s /etc/nginx/http.d/my-site.conf /etc/nginx/http.d/default.conf 2>/dev/null; then
            echo "[docker-entrypoint] Renomeando my-site.conf para .bak para evitar duplicação (será restaurado no próximo restart)..."
            mv /etc/nginx/http.d/my-site.conf /etc/nginx/http.d/my-site.conf.bak 2>/dev/null || true
        fi
    fi
    
    # default-ssl.conf já está no diretório (copiado pelo Dockerfile) e será carregado automaticamente
    
    echo "[docker-entrypoint] Arquivos de configuração para nginx-unified:"
    ls -la /etc/nginx/http.d/*.conf 2>/dev/null || echo "Nenhum arquivo .conf encontrado"
    
    # Validar configuração antes de iniciar
    echo "[docker-entrypoint] Testando configuração do Nginx..."
    nginx -t 2>&1 || {
        echo "[docker-entrypoint] ERRO na validação da configuração do Nginx!"
        echo "[docker-entrypoint] Verificando arquivos de configuração:"
        ls -la /etc/nginx/http.d/*.conf 2>/dev/null || true
        echo "[docker-entrypoint] Verificando se há variáveis não substituídas:"
        grep -r "PHP_SERVICE\|php_service" /etc/nginx/http.d/*.conf 2>/dev/null || true
        exit 1
    }
fi

# Executar o comando padrão do Nginx
exec "$@"
