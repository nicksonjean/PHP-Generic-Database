#!/bin/bash

# Verificar se WEB_SERVER foi passado como argumento
WEB_SERVER="nginx"
if [ "$1" = "--apache" ]; then
    WEB_SERVER="apache"
elif [ "$1" = "--nginx" ]; then
    WEB_SERVER="nginx"
elif [ -z "$1" ]; then
    # Se não foi passado argumento, perguntar ao usuário
    echo "Choose the web server:"
    echo "[1] Nginx (default)"
    echo "[2] Apache"
    read -p "Enter your choice (1 or 2): " choice
    if [ "$choice" = "2" ]; then
        WEB_SERVER="apache"
    fi
fi

echo "========================================"
echo "Setup All PHP Versions - $WEB_SERVER"
echo "========================================"
echo ""

echo "[1/6] Setting up PHP 8.0 on port 8000/8043..."
if [ "$WEB_SERVER" = "apache" ]; then
    ./setup.sh --build-arg PHP_VERSION=8.0 --build-arg PHP_PORT=8000 --run "docker compose up -d apache80"
else
    ./setup.sh --build-arg PHP_VERSION=8.0 --build-arg PHP_PORT=8000 --run "docker compose up -d app80 nginx80"
fi
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.0"
    exit 1
fi
echo ""

echo "[2/6] Setting up PHP 8.1 on port 8100/8143..."
if [ "$WEB_SERVER" = "apache" ]; then
    ./setup.sh --build-arg PHP_VERSION=8.1 --build-arg PHP_PORT=8100 --run "docker compose up -d apache81"
else
    ./setup.sh --build-arg PHP_VERSION=8.1 --build-arg PHP_PORT=8100 --run "docker compose up -d app81 nginx81"
fi
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.1"
    exit 1
fi
echo ""

echo "[3/6] Setting up PHP 8.2 on port 8200/8243..."
if [ "$WEB_SERVER" = "apache" ]; then
    ./setup.sh --build-arg PHP_VERSION=8.2 --build-arg PHP_PORT=8200 --run "docker compose up -d apache82"
else
    ./setup.sh --build-arg PHP_VERSION=8.2 --build-arg PHP_PORT=8200 --run "docker compose up -d app82 nginx82"
fi
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.2"
    exit 1
fi
echo ""

echo "[4/6] Setting up PHP 8.3 on port 8300/8343..."
if [ "$WEB_SERVER" = "apache" ]; then
    ./setup.sh --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d apache83"
else
    ./setup.sh --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d app83 nginx83"
fi
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.3"
    exit 1
fi
echo ""

echo "[5/6] Setting up PHP 8.4 on port 8400/8443..."
if [ "$WEB_SERVER" = "apache" ]; then
    ./setup.sh --build-arg PHP_VERSION=8.4 --build-arg PHP_PORT=8400 --run "docker compose up -d apache84"
else
    ./setup.sh --build-arg PHP_VERSION=8.4 --build-arg PHP_PORT=8400 --run "docker compose up -d app84 nginx84"
fi
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.4"
    exit 1
fi
echo ""

echo "[6/6] Setting up PHP 8.5 on port 8500/8543..."
if [ "$WEB_SERVER" = "apache" ]; then
    ./setup.sh --build-arg PHP_VERSION=8.5 --build-arg PHP_PORT=8500 --run "docker compose up -d apache85"
else
    ./setup.sh --build-arg PHP_VERSION=8.5 --build-arg PHP_PORT=8500 --run "docker compose up -d app85 nginx85"
fi
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.5"
    exit 1
fi
echo ""

echo "========================================"
echo "All PHP versions setup completed!"
echo "========================================"
echo ""
echo "PHP 8.0: http://localhost:8000 or https://localhost:8043"
echo "PHP 8.1: http://localhost:8100 or https://localhost:8143"
echo "PHP 8.2: http://localhost:8200 or https://localhost:8243"
echo "PHP 8.3: http://localhost:8300 or https://localhost:8343"
echo "PHP 8.4: http://localhost:8400 or https://localhost:8443"
echo "PHP 8.5: http://localhost:8500 or https://localhost:8543"
echo ""

