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
    # Nota: default-ssl-simple.conf foi unificado em default-ssl.conf
    # Não é mais necessário restaurar default-ssl-simple.conf
    
    # Verificar se o arquivo default-simple.conf existe
    if [ ! -f /etc/nginx/http.d/default-simple.conf ]; then
        echo "[docker-entrypoint] ERRO: /etc/nginx/http.d/default-simple.conf não encontrado!"
        echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
        echo "[docker-entrypoint] Execute: docker compose build nginx-php-8.0"
        echo "[docker-entrypoint] Arquivos disponíveis em /etc/nginx/http.d/:"
        ls -la /etc/nginx/http.d/ 2>/dev/null || true
        exit 1
    fi
    
    # Verificar se o arquivo default-ssl.conf existe (deve ter sido copiado pelo Dockerfile)
    if [ ! -f /etc/nginx/http.d/default-ssl.conf ]; then
        echo "[docker-entrypoint] AVISO: /etc/nginx/http.d/default-ssl.conf não encontrado!"
        echo "[docker-entrypoint] Tentando restaurar do backup..."
        if [ -f /etc/nginx/http.d/default-ssl.conf.bak ]; then
            cp /etc/nginx/http.d/default-ssl.conf.bak /etc/nginx/http.d/default-ssl.conf 2>/dev/null || true
            echo "[docker-entrypoint] default-ssl.conf restaurado do backup"
        else
            echo "[docker-entrypoint] ERRO: default-ssl.conf não encontrado e sem backup!"
            echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
            echo "[docker-entrypoint] Execute: docker compose build nginx-php-8.0"
            echo "[docker-entrypoint] Arquivos disponíveis em /etc/nginx/http.d/:"
            ls -la /etc/nginx/http.d/ 2>/dev/null || true
            exit 1
        fi
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
    
    # Remover default.conf apenas se vamos gerar novos
    # IMPORTANTE: NÃO remover default-ssl.conf aqui, pois ele foi copiado pelo Dockerfile
    # e precisamos dele para processar. Se foi removido anteriormente, restaurar do backup
    if [ "$SKIP_GENERATION" != "true" ]; then
        rm -f /etc/nginx/http.d/default.conf 2>/dev/null || true
        
        # Verificar se default-ssl.conf existe (deve ter sido copiado pelo Dockerfile)
        if [ ! -f /etc/nginx/http.d/default-ssl.conf ]; then
            echo "[docker-entrypoint] default-ssl.conf não encontrado, tentando restaurar..."
            if [ -f /etc/nginx/http.d/default-ssl.conf.bak ]; then
                echo "[docker-entrypoint] Restaurando default-ssl.conf do backup..."
                cp /etc/nginx/http.d/default-ssl.conf.bak /etc/nginx/http.d/default-ssl.conf 2>/dev/null || true
            else
                echo "[docker-entrypoint] ERRO: default-ssl.conf não encontrado e sem backup!"
                echo "[docker-entrypoint] O arquivo deveria ter sido copiado pelo Dockerfile."
                echo "[docker-entrypoint] Arquivos disponíveis em /etc/nginx/http.d/:"
                ls -la /etc/nginx/http.d/ 2>/dev/null || true
                echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
                echo "[docker-entrypoint] Execute: docker compose build nginx-php-8.0"
                exit 1
            fi
        fi
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
        
        # Substituir ${PHP_SERVICE} no default-ssl.conf (HTTPS)
        # NOTA: O nginx escuta na porta 443 dentro do container (porta padrão SSL)
        # O Docker Compose mapeia a porta externa (SSL_PORT do .env) para a porta 443 do container
        # O arquivo default-ssl.conf agora é unificado e contém o bloco da porta 443 com ${PHP_SERVICE}
        # IMPORTANTE: O arquivo deve existir (copiado pelo Dockerfile ou restaurado do backup)
        if [ -f /etc/nginx/http.d/default-ssl.conf ]; then
            # Criar backup ANTES de processar o arquivo
            if [ ! -f /etc/nginx/http.d/default-ssl.conf.bak ]; then
                cp /etc/nginx/http.d/default-ssl.conf /etc/nginx/http.d/default-ssl.conf.bak 2>/dev/null || true
            fi
            
            echo "[docker-entrypoint] Processando default-ssl.conf (HTTPS) para modo genérico..."
            echo "[docker-entrypoint] Variáveis de ambiente disponíveis:"
            echo "[docker-entrypoint]   PHP_SERVICE=${PHP_SERVICE}"
            echo "[docker-entrypoint]   SSL_PORT=${SSL_PORT:-NÃO DEFINIDO} (porta externa mapeada para 443 no container)"
            
            echo "[docker-entrypoint] Conteúdo original do default-ssl.conf (primeiras linhas):"
            head -5 /etc/nginx/http.d/default-ssl.conf || true
            
            # IMPORTANTE: No modo genérico, precisamos remover os blocos das portas 8043-8543
            # para evitar conflitos, pois esses serviços PHP-FPM podem não existir
            # Primeiro, remover os blocos das portas 8043-8543 (modo unificado)
            echo "[docker-entrypoint] Removendo blocos das portas 8043-8543 (modo unificado) do default-ssl.conf..."
            echo "[docker-entrypoint] Mantendo apenas o bloco da porta 443 (modo genérico)..."
            
            # Remover desde o comentário "# Modo Unificado" até o final do arquivo
            awk '
                BEGIN { skip=0 }
                /^# Modo Unificado - Múltiplas versões PHP/ { skip=1; next }
                skip { next }
                !skip { print }
            ' /etc/nginx/http.d/default-ssl.conf > /etc/nginx/http.d/default-ssl.conf.tmp
            
            # Agora substituir a variável PHP_SERVICE no bloco restante (porta 443)
            cat /etc/nginx/http.d/default-ssl.conf.tmp | \
                sed "s/\${PHP_SERVICE}/${PHP_SERVICE}/g" | \
                sed "s/\$PHP_SERVICE/${PHP_SERVICE}/g" > /etc/nginx/http.d/default-ssl.conf && \
                rm -f /etc/nginx/http.d/default-ssl.conf.tmp
            
            echo "[docker-entrypoint] Blocos das portas 8043-8543 removidos, apenas porta 443 mantida"
            
            echo "[docker-entrypoint] Conteúdo gerado do default-ssl.conf (primeiras linhas):"
            head -10 /etc/nginx/http.d/default-ssl.conf || true
            
            # Verificar se a substituição SSL funcionou (apenas no bloco da porta 443)
            if grep -A 20 "listen 443 ssl" /etc/nginx/http.d/default-ssl.conf | grep -q '\${PHP_SERVICE}'; then
                echo "[docker-entrypoint] ERRO: Variável \${PHP_SERVICE} não foi substituída no bloco SSL da porta 443!"
                exit 1
            fi
            if grep -A 20 "listen 443 ssl" /etc/nginx/http.d/default-ssl.conf | grep -q '\$PHP_SERVICE'; then
                echo "[docker-entrypoint] ERRO: Variável \$PHP_SERVICE não foi substituída no bloco SSL da porta 443!"
                exit 1
            fi
            
            # Verificar se os blocos das portas 8043-8543 foram removidos
            if grep -q "listen 8043\|listen 8143\|listen 8243\|listen 8343\|listen 8443\|listen 8543" /etc/nginx/http.d/default-ssl.conf; then
                echo "[docker-entrypoint] AVISO: Ainda existem blocos das portas 8043-8543 no arquivo!"
                echo "[docker-entrypoint] Isso pode causar conflitos no modo genérico."
            fi
            
            echo "[docker-entrypoint] Configuração SSL gerada com sucesso:"
            echo "[docker-entrypoint] Porta SSL configurada (porta 443):"
            grep -A 2 "listen 443 ssl" /etc/nginx/http.d/default-ssl.conf || true
            echo "[docker-entrypoint] FastCGI configurado (porta 443):"
            grep -A 5 "listen 443 ssl" /etc/nginx/http.d/default-ssl.conf | grep "fastcgi_pass" || true
        else
            echo "[docker-entrypoint] ERRO: default-ssl.conf não encontrado após tentativas de restauração!"
            echo "[docker-entrypoint] Arquivos disponíveis em /etc/nginx/http.d/:"
            ls -la /etc/nginx/http.d/ 2>/dev/null || true
            echo "[docker-entrypoint] Isso geralmente significa que a imagem precisa ser reconstruída."
            echo "[docker-entrypoint] Execute: docker compose build nginx-php-8.0"
            exit 1
        fi
        
        # IMPORTANTE: Criar backup dos templates antes de removê-los para restaurar em futuros restarts
        # Isso garante que os templates estarão disponíveis mesmo após reiniciar o container
        if [ -f /etc/nginx/http.d/default-simple.conf ]; then
            cp /etc/nginx/http.d/default-simple.conf /etc/nginx/http.d/default-simple.conf.bak 2>/dev/null || true
        fi
        # Nota: default-ssl-simple.conf foi unificado em default-ssl.conf
        # O backup do default-ssl.conf já foi criado acima, antes de processar o arquivo
        
        # Remover os arquivos template para evitar que o nginx tente carregá-los
        # Mas manteremos os backups para restaurar em futuros restarts
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
    # O arquivo é unificado e contém todos os blocos server (porta 443 com variável + portas 8043-8543 hardcoded)
    # Como PHP_SERVICE não está definido, precisamos remover o bloco da porta 443 para evitar erros
    # (o nginx não consegue processar variáveis não substituídas)
    if [ -f /etc/nginx/http.d/default-ssl.conf ]; then
        echo "[docker-entrypoint] Removendo bloco da porta 443 (modo genérico) do default-ssl.conf..."
        echo "[docker-entrypoint] Mantendo apenas os blocos das portas 8043-8543 (modo unificado)..."
        
        # Método mais simples e seguro: copiar apenas a partir da linha "# Modo Unificado"
        # Isso garante que mantemos todos os blocos das portas 8043-8543 intactos
        sed -n '/^# Modo Unificado - Múltiplas versões PHP/,$p' /etc/nginx/http.d/default-ssl.conf > /etc/nginx/http.d/default-ssl.conf.tmp
        
        # Verificar se o arquivo foi processado corretamente
        if [ ! -s /etc/nginx/http.d/default-ssl.conf.tmp ]; then
            echo "[docker-entrypoint] ERRO: Arquivo processado está vazio!"
            rm -f /etc/nginx/http.d/default-ssl.conf.tmp
            exit 1
        fi
        
        # Verificar se o bloco do PHP 8.0 está presente
        if ! grep -q "listen 8043 ssl" /etc/nginx/http.d/default-ssl.conf.tmp; then
            echo "[docker-entrypoint] ERRO: Bloco do PHP 8.0 (porta 8043) não encontrado após processamento!"
            rm -f /etc/nginx/http.d/default-ssl.conf.tmp
            exit 1
        fi
        
        # Verificar se o bloco da porta 443 foi removido
        if grep -q "listen 443 ssl" /etc/nginx/http.d/default-ssl.conf.tmp; then
            echo "[docker-entrypoint] AVISO: Bloco da porta 443 ainda está presente!"
            echo "[docker-entrypoint] Isso não deveria acontecer. Verificando arquivo original..."
            exit 1
        fi
        
        mv /etc/nginx/http.d/default-ssl.conf.tmp /etc/nginx/http.d/default-ssl.conf
        echo "[docker-entrypoint] Bloco da porta 443 removido com sucesso"
        
        # Validação final: verificar se todos os blocos esperados estão presentes
        echo "[docker-entrypoint] Validando blocos SSL restantes..."
        for port in 8043 8143 8243 8343 8443 8543; do
            if ! grep -q "listen $port ssl" /etc/nginx/http.d/default-ssl.conf; then
                echo "[docker-entrypoint] AVISO: Bloco da porta $port não encontrado!"
            else
                echo "[docker-entrypoint] OK: Bloco da porta $port encontrado"
            fi
        done
    fi
    
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
