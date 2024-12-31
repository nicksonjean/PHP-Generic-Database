#!/bin/bash

# Inicializa variáveis
NEXTARG=false
RUNCOMMAND=false
RUNVALUE=""
PHP_VERSION=""
PHP_PORT=""

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
            fi
        elif $RUNCOMMAND; then
            RUNVALUE="$RUNVALUE $ARG"
        fi
    fi
done

# Cria um arquivo temporário para o novo conteúdo do env.docker
TEMPFILE=$(mktemp)

# Verifica e atualiza as variáveis no arquivo env.docker
PHP_VERSION_EXIST=false
PHP_PORT_EXIST=false
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
