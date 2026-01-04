#!/bin/bash

# Inicializa variáveis
NEXTARG=false
RUNCOMMAND=false
RUNVALUE=""
PHP_VERSION=""
PHP_PORT=""
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

# Cria um arquivo temporário para o novo conteúdo do env.docker
TEMPFILE=$(mktemp)

# Verifica e atualiza as variáveis no arquivo env.docker
PHP_VERSION_EXIST=false
PHP_PORT_EXIST=false
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
    echo "Executando: $RUNVALUE"
    eval $RUNVALUE
fi
