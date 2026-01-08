@echo off
setlocal enabledelayedexpansion

rem Verificar se WEB_SERVER foi passado como argumento
set "WEB_SERVER=nginx"
if "%1"=="--apache" (
    set "WEB_SERVER=apache"
) else if "%1"=="--nginx" (
    set "WEB_SERVER=nginx"
) else if "%1"=="--frankenphp" (
    set "WEB_SERVER=frankenphp"
) else if "%1"=="" (
    rem Se não foi passado argumento, perguntar ao usuário
    echo Choose the web server:
    echo [1] Nginx ^(default^)
    echo [2] Apache
    echo [3] FrankenPHP
    set /p "choice=Enter your choice (1, 2 or 3): "
    if "!choice!"=="2" set "WEB_SERVER=apache"
    if "!choice!"=="3" set "WEB_SERVER=frankenphp"
)

echo ========================================
echo Setup All PHP Versions - !WEB_SERVER!
echo ========================================
echo.

if "!WEB_SERVER!"=="apache" (
    echo Building and starting all Apache services...
    docker compose build php-8.0-apache php-8.1-apache php-8.2-apache php-8.3-apache php-8.4-apache php-8.5-apache
    if errorlevel 1 (
        echo ERROR: Failed to build Apache services
        exit /b 1
    )
    docker compose up -d php-8.0-apache php-8.1-apache php-8.2-apache php-8.3-apache php-8.4-apache php-8.5-apache
    if errorlevel 1 (
        echo ERROR: Failed to start Apache services
        exit /b 1
    )
) else if "!WEB_SERVER!"=="frankenphp" (
    echo Building and starting all FrankenPHP services...
    echo NOTE: FrankenPHP supports only PHP 8.2, 8.3, 8.4 and 8.5
    docker compose build php-8.2-frankenphp php-8.3-frankenphp php-8.4-frankenphp php-8.5-frankenphp
    if errorlevel 1 (
        echo ERROR: Failed to build FrankenPHP services
        exit /b 1
    )
    docker compose up -d php-8.2-frankenphp php-8.3-frankenphp php-8.4-frankenphp php-8.5-frankenphp
    if errorlevel 1 (
        echo ERROR: Failed to start FrankenPHP services
        exit /b 1
    )
) else (
    echo Building all PHP-FPM services...
    docker compose build php-8.0-fpm php-8.1-fpm php-8.2-fpm php-8.3-fpm php-8.4-fpm php-8.5-fpm
    if errorlevel 1 (
        echo ERROR: Failed to build PHP-FPM services
        exit /b 1
    )
    echo Building Nginx unified service...
    docker compose build nginx-unified
    if errorlevel 1 (
        echo ERROR: Failed to build Nginx unified service
        exit /b 1
    )
    echo Starting all PHP-FPM and Nginx unified services...
    docker compose up -d php-8.0-fpm php-8.1-fpm php-8.2-fpm php-8.3-fpm php-8.4-fpm php-8.5-fpm nginx-unified
    if errorlevel 1 (
        echo ERROR: Failed to start services
        exit /b 1
    )
)
echo.

echo ========================================
echo All PHP versions setup completed!
echo ========================================
echo.
if "!WEB_SERVER!"=="frankenphp" (
    echo PHP 8.2: http://localhost:8200 or https://localhost:8243
    echo PHP 8.3: http://localhost:8300 or https://localhost:8343
    echo PHP 8.4: http://localhost:8400 or https://localhost:8443
    echo PHP 8.5: http://localhost:8500 or https://localhost:8543
    echo.
    echo NOTE: FrankenPHP supports only PHP 8.2, 8.3, 8.4 and 8.5
) else (
    echo PHP 8.0: http://localhost:8000 or https://localhost:8043
    echo PHP 8.1: http://localhost:8100 or https://localhost:8143
    echo PHP 8.2: http://localhost:8200 or https://localhost:8243
    echo PHP 8.3: http://localhost:8300 or https://localhost:8343
    echo PHP 8.4: http://localhost:8400 or https://localhost:8443
    echo PHP 8.5: http://localhost:8500 or https://localhost:8543
)
echo.

endlocal

