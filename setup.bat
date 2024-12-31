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
                )
            )

            rem Quando encontrar --run, captura o comando e prepara para execução
            if !RUNCOMMAND! == true (
                set "RUNVALUE=!RUNVALUE! %%A"
            )
        )
    )
)

rem Cria um arquivo temporário para o novo conteúdo do env.docker
set "TEMPFILE=%SOURCE%.tmp"

rem Verifica e atualiza as variáveis no arquivo env.docker
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
    echo !LINE!
)) > "%TEMPFILE%"

rem Adiciona as variáveis ausentes
if not defined PHP_VERSION_EXIST if defined PHP_VERSION (
    ::echo Adicionando PHP_VERSION=%PHP_VERSION%
    echo PHP_VERSION=%PHP_VERSION%>>"%TEMPFILE%"
)

if not defined PHP_PORT_EXIST if defined PHP_PORT (
    ::echo Adicionando PHP_PORT=%PHP_PORT%
    echo PHP_PORT=%PHP_PORT%>>"%TEMPFILE%"
)

rem Substitui o arquivo env.docker pelo arquivo atualizado
move /y "%TEMPFILE%" "%SOURCE%" >nul

rem Copiar o conteúdo do arquivo atualizado para o arquivo de destino
copy /y "%SOURCE%" "%TARGET%" >nul

rem Executa o comando --run após pegar todos os argumentos, se presente
if !RUNCOMMAND! == true (
    rem Remove as aspas do comando (caso tenha sido passado)
    set "RUNVALUE=!RUNVALUE:"=!"
    echo Executando: !RUNVALUE!
    call !RUNVALUE!
)

endlocal
