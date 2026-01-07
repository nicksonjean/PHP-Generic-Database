@echo off
setlocal enabledelayedexpansion

rem Inicializa variáveis
set "NEXTARG=false"
set "KEY="
set "VALUE="
set "RUNCOMMAND=false"
set "RUNVALUE="
set "PHP_VERSION="
set "PHP_PORT="
set "SSL_PORT="
set "WEB_SERVER=nginx"

rem Definir arquivos de origem e destino
set "SOURCE=.env.docker"
set "TARGET=.env"

rem Verificar se o arquivo de origem existe
if not exist "%SOURCE%" (
    echo Arquivo %SOURCE% não encontrado.
    exit /b 1
)

rem Itera sobre os parâmetros
for %%A in (%*) do (
    rem Quando encontrar --build-arg, marca o próximo argumento como chave-valor
    if "%%A"=="--build-arg" (
        set NEXTARG=true
    ) else (
        rem Quando encontrar --run, marca o comando para execução
        if "%%A"=="--run" (
            set RUNCOMMAND=true
            set "RUNVALUE="
        ) else (
            rem Se for um parâmetro para --build-arg
            if !NEXTARG! == true (
                set "KEY=%%A"
                set NEXTARG=false
            ) else (
                rem Quando encontrar o valor, concatena chave=valor
                set "VALUE=%%A"
                if not !RUNCOMMAND! == true (
                    echo !KEY!=!VALUE!
                    rem Salva os valores das envs em variáveis específicas
                    if "!KEY!"=="PHP_VERSION" set "PHP_VERSION=!VALUE!"
                    if "!KEY!"=="PHP_PORT" set "PHP_PORT=!VALUE!"
                    if "!KEY!"=="SSL_PORT" set "SSL_PORT=!VALUE!"
                    if "!KEY!"=="WEB_SERVER" set "WEB_SERVER=!VALUE!"
                )
            )

            rem Quando encontrar --run, captura o comando e prepara para execução
            if !RUNCOMMAND! == true (
                set "RUNVALUE=!RUNVALUE! %%A"
            )
        )
    )
)

rem Calcula PHP_BASE_TAG baseado na versão do PHP
rem PHP 8.0 não tem imagens bookworm, então não define no .env (usa vazio no Dockerfile)
rem PHP 8.1+ usa -bookworm para compatibilidade com as bibliotecas
set "PHP_BASE_TAG=-bookworm"
if defined PHP_VERSION (
    if "!PHP_VERSION!"=="8.0" (
        set "PHP_BASE_TAG="
        set "PHP_BASE_TAG_IS_EMPTY=true"
    )
)

rem Calcula PHP_PORT e SSL_PORT automaticamente se não foram fornecidos
rem Padrão: PHP 8.0 -> 8000/8043, 8.1 -> 8100/8143, 8.2 -> 8200/8243, 8.3 -> 8300/8343, etc.
if defined PHP_VERSION (
    if not defined PHP_PORT (
        rem Remove o ponto da versão (8.3 -> 83) e multiplica por 100 (8300)
        set "VERSION_NUM=!PHP_VERSION:.=!"
        set /a "PHP_PORT=!VERSION_NUM! * 100"
        echo PHP_PORT=!PHP_PORT!
    )
    if not defined SSL_PORT (
        rem Calcula porta SSL: PHP_PORT + 43 (8300 + 43 = 8343)
        if defined PHP_PORT (
            set /a "SSL_PORT=!PHP_PORT! + 43"
            echo SSL_PORT=!SSL_PORT!
        ) else (
            rem Se PHP_PORT não foi calculado ainda, calcula diretamente
            set "VERSION_NUM=!PHP_VERSION:.=!"
            set /a "TEMP_PORT=!VERSION_NUM! * 100"
            set /a "SSL_PORT=!TEMP_PORT! + 43"
            echo SSL_PORT=!SSL_PORT!
        )
    )
)

rem Cria um arquivo temporário para o novo conteúdo do env.docker
set "TEMPFILE=%SOURCE%.tmp"

rem Verifica e atualiza as variáveis no arquivo env.docker
set "PHP_PORT_EXIST=false"
set "SSL_PORT_EXIST=false"
(for /f "tokens=1,2 delims==" %%B in ('type "%SOURCE%"') do (
    set "LINE=%%B=%%C"
    if "%%B"=="PHP_VERSION" (
        if not "%%C"=="%PHP_VERSION%" (
            ::echo Atualizando PHP_VERSION de %%C para %PHP_VERSION%
            set "LINE=PHP_VERSION=%PHP_VERSION%"
        )
        set "PHP_VERSION_EXIST=true"
    )
    if "%%B"=="PHP_PORT" (
        if not "%%C"=="%PHP_PORT%" (
            ::echo Atualizando PHP_PORT de %%C para %PHP_PORT%
            set "LINE=PHP_PORT=%PHP_PORT%"
        )
        set "PHP_PORT_EXIST=true"
    )
    if "%%B"=="SSL_PORT" (
        if not "%%C"=="%SSL_PORT%" (
            ::echo Atualizando SSL_PORT de %%C para %SSL_PORT%
            set "LINE=SSL_PORT=%SSL_PORT%"
        )
        set "SSL_PORT_EXIST=true"
    )
    if "%%B"=="PHP_BASE_TAG" (
        if "!PHP_BASE_TAG!"=="" (
            :: Para PHP 8.0, remove PHP_BASE_TAG do .env (não escreve a linha, será removida)
            set "PHP_BASE_TAG_REMOVE=true"
        ) else (
            if not "%%C"=="!PHP_BASE_TAG!" (
                ::echo Atualizando PHP_BASE_TAG de %%C para !PHP_BASE_TAG!
                set "LINE=PHP_BASE_TAG=!PHP_BASE_TAG!"
            )
        )
        set "PHP_BASE_TAG_EXIST=true"
        set "SKIP_LINE=true"
    )
    if not defined SKIP_LINE (
        echo !LINE!
    ) else (
        set "SKIP_LINE="
    )
)) > "%TEMPFILE%"

rem Adiciona as variáveis ausentes
if not defined PHP_VERSION_EXIST if defined PHP_VERSION (
    ::echo Adicionando PHP_VERSION=%PHP_VERSION%
    echo PHP_VERSION=%PHP_VERSION%>>"%TEMPFILE%"
)

if "!PHP_PORT_EXIST!"=="false" if defined PHP_PORT (
    ::echo Adicionando PHP_PORT=%PHP_PORT%
    echo PHP_PORT=%PHP_PORT%>>"%TEMPFILE%"
)

if "!SSL_PORT_EXIST!"=="false" if defined SSL_PORT (
    ::echo Adicionando SSL_PORT=%SSL_PORT%
    echo SSL_PORT=%SSL_PORT%>>"%TEMPFILE%"
)

if not defined PHP_BASE_TAG_EXIST if defined PHP_VERSION (
    ::echo Adicionando PHP_BASE_TAG calculado baseado na versão (apenas se não for vazio)
    if not "!PHP_BASE_TAG!"=="" (
        echo PHP_BASE_TAG=!PHP_BASE_TAG!>>"%TEMPFILE%"
    )
)

rem Substitui o arquivo env.docker pelo arquivo atualizado
move /y "%TEMPFILE%" "%SOURCE%" >nul

rem Copiar o conteúdo do arquivo atualizado para o arquivo de destino
copy /y "%SOURCE%" "%TARGET%" >nul

rem Executa o comando --run após pegar todos os argumentos, se presente
if !RUNCOMMAND! == true (
    rem Remove as aspas do comando (caso tenha sido passado)
    set "RUNVALUE=!RUNVALUE:"=!"
    
    rem Substituir serviços genéricos por específicos baseado na versão PHP
    rem Isso evita conflitos quando executar múltiplas versões simultaneamente
    if defined PHP_VERSION (
        rem Verificar se a versão PHP é suportada pelo FrankenPHP (apenas 8.2+)
        set "FRANKENPHP_SUPPORTED=false"
        if "!PHP_VERSION!"=="8.2" set "FRANKENPHP_SUPPORTED=true"
        if "!PHP_VERSION!"=="8.3" set "FRANKENPHP_SUPPORTED=true"
        if "!PHP_VERSION!"=="8.4" set "FRANKENPHP_SUPPORTED=true"
        if "!PHP_VERSION!"=="8.5" set "FRANKENPHP_SUPPORTED=true"
        
        rem Processar palavra por palavra para fazer substituições
        set "NEWVALUE="
        for %%I in (!RUNVALUE!) do (
            set "TOKEN=%%I"
            if "!TOKEN!"=="apache" (
                set "NEWVALUE=!NEWVALUE! php-!PHP_VERSION!-apache"
            ) else (
                if "!TOKEN!"=="fpm" (
                    set "NEWVALUE=!NEWVALUE! php-!PHP_VERSION!-fpm"
                ) else (
                    if "!TOKEN!"=="nginx" (
                        set "NEWVALUE=!NEWVALUE! nginx-php-!PHP_VERSION!"
                    ) else (
                        if "!TOKEN!"=="frankenphp" (
                            if "!FRANKENPHP_SUPPORTED!"=="true" (
                                set "NEWVALUE=!NEWVALUE! php-!PHP_VERSION!-frankenphp"
                            ) else (
                                echo ERROR: FrankenPHP suporta apenas PHP 8.2, 8.3, 8.4 e 8.5. Versao fornecida: !PHP_VERSION!
                                exit /b 1
                            )
                        ) else (
                            rem Outros serviços não são substituídos
                            set "NEWVALUE=!NEWVALUE! !TOKEN!"
                        )
                    )
                )
            )
        )
        set "RUNVALUE=!NEWVALUE:~1!"
    )
    
    echo Executando: !RUNVALUE!
    call !RUNVALUE!
)

endlocal
