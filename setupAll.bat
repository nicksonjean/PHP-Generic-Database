@echo off
setlocal enabledelayedexpansion

echo ========================================
echo Setup All PHP Versions
echo ========================================
echo.

echo [1/6] Setting up PHP 8.0 on port 8000...
call setup.bat --build-arg PHP_VERSION=8.0 --build-arg PHP_PORT=8000 --run "docker compose up -d app80"
if errorlevel 1 (
    echo ERROR: Failed to setup PHP 8.0
    exit /b 1
)
echo.

echo [2/6] Setting up PHP 8.1 on port 8100...
call setup.bat --build-arg PHP_VERSION=8.1 --build-arg PHP_PORT=8100 --run "docker compose up -d app81"
if errorlevel 1 (
    echo ERROR: Failed to setup PHP 8.1
    exit /b 1
)
echo.

echo [3/6] Setting up PHP 8.2 on port 8200...
call setup.bat --build-arg PHP_VERSION=8.2 --build-arg PHP_PORT=8200 --run "docker compose up -d app82"
if errorlevel 1 (
    echo ERROR: Failed to setup PHP 8.2
    exit /b 1
)
echo.

echo [4/6] Setting up PHP 8.3 on port 8300...
call setup.bat --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d app83"
if errorlevel 1 (
    echo ERROR: Failed to setup PHP 8.3
    exit /b 1
)
echo.

echo [5/6] Setting up PHP 8.4 on port 8400...
call setup.bat --build-arg PHP_VERSION=8.4 --build-arg PHP_PORT=8400 --run "docker compose up -d app84"
if errorlevel 1 (
    echo ERROR: Failed to setup PHP 8.4
    exit /b 1
)
echo.

echo [6/6] Setting up PHP 8.5 on port 8500...
call setup.bat --build-arg PHP_VERSION=8.5 --build-arg PHP_PORT=8500 --run "docker compose up -d app85"
if errorlevel 1 (
    echo ERROR: Failed to setup PHP 8.5
    exit /b 1
)
echo.

echo ========================================
echo All PHP versions setup completed!
echo ========================================
echo.
echo PHP 8.0: http://localhost:8000
echo PHP 8.1: http://localhost:8100
echo PHP 8.2: http://localhost:8200
echo PHP 8.3: http://localhost:8300
echo PHP 8.4: http://localhost:8400
echo PHP 8.5: http://localhost:8500
echo.

endlocal

