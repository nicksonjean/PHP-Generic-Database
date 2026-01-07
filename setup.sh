#!/bin/bash

# Inicializa variáveis
NEXTARG=false
RUNCOMMAND=false
RUNVALUE=""
PHP_VERSION=""
PHP_PORT=""
SSL_PORT=""
WEB_SERVER="nginx"

# Define arquivos de origem e destino
SOURCE=".env.docker"
TARGET=".env"

# Verificar se o arquivo de origem existe
if [ ! -f "$SOURCE" ]; then
    echo "Arquivo $SOURCE não encontrado."
    exit 1
fi

# Itera sobre os argumentos
for ARG in "$@"; do
    if [ "$ARG" = "--build-arg" ]; then
        NEXTARG=true
    elif [ "$ARG" = "--run" ]; then
        RUNCOMMAND=true
        RUNVALUE=""
    else
        if $NEXTARG; then
            KEY=$(echo "$ARG" | cut -d '=' -f 1)
            VALUE=$(echo "$ARG" | cut -d '=' -f 2)
            echo "$KEY=$VALUE"
            NEXTARG=false

            # Salva os valores das envs em variáveis específicas
            if [ "$KEY" = "PHP_VERSION" ]; then
                PHP_VERSION="$VALUE"
            elif [ "$KEY" = "PHP_PORT" ]; then
                PHP_PORT="$VALUE"
            elif [ "$KEY" = "SSL_PORT" ]; then
                SSL_PORT="$VALUE"
            elif [ "$KEY" = "WEB_SERVER" ]; then
                WEB_SERVER="$VALUE"
            fi
        elif $RUNCOMMAND; then
            RUNVALUE="$RUNVALUE $ARG"
        fi
    fi
done

# Calcula PHP_BASE_TAG baseado na versão do PHP
# PHP 8.0 não tem imagens bookworm, então usa tag vazia
# PHP 8.1+ usa -bookworm para compatibilidade com as bibliotecas
PHP_BASE_TAG="-bookworm"
if [ -n "$PHP_VERSION" ]; then
    case "$PHP_VERSION" in
        8.0)
            PHP_BASE_TAG=""
            ;;
    esac
fi

# Calcula PHP_PORT e SSL_PORT automaticamente se não foram fornecidos
# Padrão: PHP 8.0 -> 8000/8043, 8.1 -> 8100/8143, 8.2 -> 8200/8243, 8.3 -> 8300/8343, etc.
if [ -n "$PHP_VERSION" ]; then
    if [ -z "$PHP_PORT" ]; then
        # Remove o ponto da versão (8.3 -> 83) e multiplica por 100 (8300)
        VERSION_NUM=$(echo "$PHP_VERSION" | tr -d '.')
        PHP_PORT=$((VERSION_NUM * 100))
        echo "PHP_PORT=$PHP_PORT"
    fi
    if [ -z "$SSL_PORT" ]; then
        # Calcula porta SSL: PHP_PORT + 43 (8300 + 43 = 8343)
        if [ -n "$PHP_PORT" ]; then
            SSL_PORT=$((PHP_PORT + 43))
            echo "SSL_PORT=$SSL_PORT"
        else
            # Se PHP_PORT não foi calculado ainda, calcula diretamente
            VERSION_NUM=$(echo "$PHP_VERSION" | tr -d '.')
            TEMP_PORT=$((VERSION_NUM * 100))
            SSL_PORT=$((TEMP_PORT + 43))
            echo "SSL_PORT=$SSL_PORT"
        fi
    fi
fi

# Cria um arquivo temporário para o novo conteúdo do env.docker
TEMPFILE=$(mktemp)

# Verifica e atualiza as variáveis no arquivo env.docker
PHP_VERSION_EXIST=false
PHP_PORT_EXIST=false
SSL_PORT_EXIST=false
PHP_BASE_TAG_EXIST=false
while IFS='=' read -r KEY VALUE; do
    LINE="$KEY=$VALUE"
    if [ "$KEY" = "PHP_VERSION" ]; then
        if [ "$VALUE" != "$PHP_VERSION" ]; then
            # echo "Atualizando PHP_VERSION de $VALUE para $PHP_VERSION"
            LINE="PHP_VERSION=$PHP_VERSION"
        fi
        PHP_VERSION_EXIST=true
    elif [ "$KEY" = "PHP_PORT" ]; then
        if [ "$VALUE" != "$PHP_PORT" ]; then
            # echo "Atualizando PHP_PORT de $VALUE para $PHP_PORT"
            LINE="PHP_PORT=$PHP_PORT"
        fi
        PHP_PORT_EXIST=true
    elif [ "$KEY" = "SSL_PORT" ]; then
        if [ "$VALUE" != "$SSL_PORT" ]; then
            # echo "Atualizando SSL_PORT de $VALUE para $SSL_PORT"
            LINE="SSL_PORT=$SSL_PORT"
        fi
        SSL_PORT_EXIST=true
    elif [ "$KEY" = "PHP_BASE_TAG" ]; then
        if [ -z "$PHP_BASE_TAG" ]; then
            # Para PHP 8.0, remove PHP_BASE_TAG do .env (não escreve a linha)
            continue
        elif [ "$VALUE" != "$PHP_BASE_TAG" ]; then
            # echo "Atualizando PHP_BASE_TAG de $VALUE para $PHP_BASE_TAG"
            LINE="PHP_BASE_TAG=$PHP_BASE_TAG"
        fi
        PHP_BASE_TAG_EXIST=true
    fi
    echo "$LINE" >> "$TEMPFILE"
done < "$SOURCE"

# Adiciona as variáveis ausentes
if ! $PHP_VERSION_EXIST && [ -n "$PHP_VERSION" ]; then
    # echo "Adicionando PHP_VERSION=$PHP_VERSION"
    echo "PHP_VERSION=$PHP_VERSION" >> "$TEMPFILE"
fi

if ! $PHP_PORT_EXIST && [ -n "$PHP_PORT" ]; then
    # echo "Adicionando PHP_PORT=$PHP_PORT"
    echo "PHP_PORT=$PHP_PORT" >> "$TEMPFILE"
fi

if ! $SSL_PORT_EXIST && [ -n "$SSL_PORT" ]; then
    # echo "Adicionando SSL_PORT=$SSL_PORT"
    echo "SSL_PORT=$SSL_PORT" >> "$TEMPFILE"
fi

if ! $PHP_BASE_TAG_EXIST && [ -n "$PHP_VERSION" ] && [ -n "$PHP_BASE_TAG" ]; then
    # echo "Adicionando PHP_BASE_TAG calculado baseado na versão (apenas se não for vazio)"
    echo "PHP_BASE_TAG=$PHP_BASE_TAG" >> "$TEMPFILE"
fi

# Substitui o arquivo env.docker pelo arquivo atualizado
mv "$TEMPFILE" "$SOURCE"

# Copiar o conteúdo do arquivo atualizado para o arquivo de destino
cp "$SOURCE" "$TARGET"

# Executa o comando --run após pegar todos os argumentos, se presente
if $RUNCOMMAND; then
    # Remove as aspas do comando (caso tenha sido passado)
    RUNVALUE=${RUNVALUE//\"/}
    
    # Substituir serviços genéricos por específicos baseado na versão PHP
    # Isso evita conflitos quando executar múltiplas versões simultaneamente
    if [ -n "$PHP_VERSION" ]; then
        # Verificar se a versão PHP é suportada pelo FrankenPHP (apenas 8.2+)
        FRANKENPHP_SUPPORTED=false
        case "$PHP_VERSION" in
            8.2|8.3|8.4|8.5)
                FRANKENPHP_SUPPORTED=true
                ;;
        esac
        
        # Substituir " apache" (com espaço antes) por " php-{versao}-apache"
        RUNVALUE=$(echo "$RUNVALUE" | sed "s/ apache/ php-${PHP_VERSION}-apache/g")
        # Substituir "fpm " (com espaço depois) ou " fpm " (com espaços) ou " fpm" (com espaço antes) por "php-{versao}-fpm"
        RUNVALUE=$(echo "$RUNVALUE" | sed "s/fpm /php-${PHP_VERSION}-fpm /g")
        RUNVALUE=$(echo "$RUNVALUE" | sed "s/ fpm / php-${PHP_VERSION}-fpm /g")
        RUNVALUE=$(echo "$RUNVALUE" | sed "s/ fpm/ php-${PHP_VERSION}-fpm/g")
        # Substituir " nginx" (com espaço antes) por " nginx-php-{versao}"
        RUNVALUE=$(echo "$RUNVALUE" | sed "s/ nginx/ nginx-php-${PHP_VERSION}/g")
        # Substituir " frankenphp" (com espaço antes) por " php-{versao}-frankenphp"
        if [ "$FRANKENPHP_SUPPORTED" = "true" ]; then
            RUNVALUE=$(echo "$RUNVALUE" | sed "s/ frankenphp/ php-${PHP_VERSION}-frankenphp/g")
        else
            if echo "$RUNVALUE" | grep -q " frankenphp"; then
                echo "ERROR: FrankenPHP suporta apenas PHP 8.2, 8.3, 8.4 e 8.5. Versão fornecida: $PHP_VERSION"
                exit 1
            fi
        fi
        # Limpar espaços duplos
        RUNVALUE=$(echo "$RUNVALUE" | sed 's/  */ /g')
    fi
    
    echo "Executando: $RUNVALUE"
    eval $RUNVALUE
fi
