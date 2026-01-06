FROM alpine:latest

# Tag do Autor
LABEL author="Nickson Jeanmerson (nickson.jeanmerson@gmail.com)"

# Argumentos de build (para exibição e documentação)
# Estes valores são passados via docker-compose.yml ou setup.bat/setup.sh
# ARG PHP_VERSION
# ARG PHP_PORT
# ARG PHP_SERVICE
# ARG SSL_PORT

# Instalar Nginx e módulo FancyIndex do repositório do Alpine
# Isso garante compatibilidade de versões entre Nginx e o módulo
RUN apk add --no-cache \
    nginx \
    nginx-mod-http-fancyindex \
    openssl \
    sed \
    gettext

# Criar diretório para certificados SSL
RUN mkdir -p /etc/nginx/ssl

# Gerar certificados SSL autoassinados
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx-selfsigned.key \
    -out /etc/nginx/ssl/nginx-selfsigned.crt \
    -subj "/C=BR/ST=Sao Paulo/L=Sao Paulo/O=PHP Generic Database/OU=Development/CN=localhost"

# Copiar script de entrada
COPY ./docker/stack-nginx/nginx/docker-entrypoint.sh /docker-entrypoint.sh
# Converter quebras de linha CRLF (Windows) para LF (Unix) e dar permissão de execução
RUN sed -i 's/\r$//' /docker-entrypoint.sh && chmod +x /docker-entrypoint.sh

# O módulo FancyIndex é carregado automaticamente pelo Alpine através de
# /etc/nginx/modules/10_http_fancyindex.conf (incluído no nginx.conf)
# Não precisamos adicionar load_module manualmente para evitar duplicação

# Remover configurações padrão do Nginx para evitar conflitos
RUN rm -f /etc/nginx/conf.d/default.conf 2>/dev/null || true && \
    rm -f /etc/nginx/http.d/default.conf 2>/dev/null || true

# Copiar configurações do Nginx
# No Alpine, blocos server devem estar em http.d, não em conf.d
# my-site.conf e default-ssl.conf são para o nginx-unified (configuração unificada)
# default.conf e default-ssl.conf (unificado) são para o nginx genérico (usa variável PHP_SERVICE)
# Nota: default-ssl.conf foi unificado e agora contém tanto o bloco da porta 443 (com variável)
# quanto os blocos das portas 8043-8543 (hardcoded) para suportar ambos os modos de operação
COPY ./docker/stack-nginx/nginx/my-site.conf /etc/nginx/http.d/my-site.conf
COPY ./docker/stack-nginx/nginx/default-ssl.conf /etc/nginx/http.d/default-ssl.conf
COPY ./docker/stack-nginx/nginx/default.conf /etc/nginx/http.d/default-simple.conf

# Copiar tema FancyIndex para o container (fallback caso volume não seja montado)
# Quando volume é montado, os arquivos estarão em /var/www/html/docker/assets/nginx-directory-listing
COPY ./docker/assets/nginx-directory-listing /var/www/html/docker/assets/nginx-directory-listing

# Criar diretórios necessários e ajustar permissões
RUN mkdir -p /var/log/nginx && \
    mkdir -p /var/cache/nginx && \
    mkdir -p /run/nginx && \
    mkdir -p /var/www/html && \
    chown -R nginx:nginx /var/log/nginx /var/cache/nginx /run/nginx /var/www/html

# Expor portas HTTP e HTTPS
# Nota: O nginx unificado escuta em múltiplas portas (8000-8500 HTTP, 8043-8543 HTTPS)
# O mapeamento de portas é feito diretamente no docker-compose.yml
EXPOSE 8000 8100 8200 8300 8400 8500 8043 8143 8243 8343 8443 8543

# Usar script de entrada personalizado
ENTRYPOINT ["/docker-entrypoint.sh"]

# Comando padrão do Nginx
CMD ["nginx", "-g", "daemon off;"]
