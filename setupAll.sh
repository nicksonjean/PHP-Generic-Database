#!/bin/bash

echo "========================================"
echo "Setup All PHP Versions"
echo "========================================"
echo ""

echo "[1/6] Setting up PHP 8.0 on port 8000..."
./setup.sh --build-arg PHP_VERSION=8.0 --build-arg PHP_PORT=8000 --run "docker compose up -d app80"
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.0"
    exit 1
fi
echo ""

echo "[2/6] Setting up PHP 8.1 on port 8100..."
./setup.sh --build-arg PHP_VERSION=8.1 --build-arg PHP_PORT=8100 --run "docker compose up -d app81"
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.1"
    exit 1
fi
echo ""

echo "[3/6] Setting up PHP 8.2 on port 8200..."
./setup.sh --build-arg PHP_VERSION=8.2 --build-arg PHP_PORT=8200 --run "docker compose up -d app82"
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.2"
    exit 1
fi
echo ""

echo "[4/6] Setting up PHP 8.3 on port 8300..."
./setup.sh --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d app83"
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.3"
    exit 1
fi
echo ""

echo "[5/6] Setting up PHP 8.4 on port 8400..."
./setup.sh --build-arg PHP_VERSION=8.4 --build-arg PHP_PORT=8400 --run "docker compose up -d app84"
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.4"
    exit 1
fi
echo ""

echo "[6/6] Setting up PHP 8.5 on port 8500..."
./setup.sh --build-arg PHP_VERSION=8.5 --build-arg PHP_PORT=8500 --run "docker compose up -d app85"
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to setup PHP 8.5"
    exit 1
fi
echo ""

echo "========================================"
echo "All PHP versions setup completed!"
echo "========================================"
echo ""
echo "PHP 8.0: http://localhost:8000"
echo "PHP 8.1: http://localhost:8100"
echo "PHP 8.2: http://localhost:8200"
echo "PHP 8.3: http://localhost:8300"
echo "PHP 8.4: http://localhost:8400"
echo "PHP 8.5: http://localhost:8500"
echo ""

