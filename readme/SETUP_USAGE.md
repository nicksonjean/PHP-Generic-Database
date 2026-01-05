# Guia de Uso: setup.bat individual vs setupAll.bat

## Resumo da Arquitetura

O `docker-compose.yml` possui dois tipos de serviços PHP:

1. **Serviços específicos** (php-8.0-fpm até php-8.5-fpm e php-8.0-apache até php-8.5-apache):
   - Valores hardcoded no docker-compose.yml
   - Usados pelo `setupAll.bat` para configurar todas as versões de uma vez
   - Container names: `php-{PHP_VERSION}-fpm` (FPM) ou `php-{PHP_VERSION}-apache` (Apache)
   - Image names: `php-{PHP_VERSION}-fpm` (FPM) ou `php-{PHP_VERSION}-apache` (Apache)
   - Tags: `php:{PHP_VERSION}-fpm` (FPM) ou `php:{PHP_VERSION}-apache` (Apache)

2. **Serviços Nginx específicos** (nginx-8.0 até nginx-8.5):
   - Valores hardcoded no docker-compose.yml
   - Usados pelo `setup.bat` para configurar versões individuais
   - Container names: `nginx-{PHP_VERSION}` (ex: `nginx-8.3`)
   - Image name: `nginx-alpine`
   - Tag: `nginx-alpine:latest`
   - O `setup.bat` automaticamente converte `nginx` para serviços específicos (`nginx-{PHP_VERSION}`)
   - Cada versão PHP tem seu próprio container Nginx independente, permitindo múltiplas versões simultaneamente

3. **Serviço Nginx Unificado**:
   - **nginx-unified**: Servidor Nginx unificado servindo todas as versões PHP (8.0-8.5)
     - Container name: `nginx-unified`
     - Image name: `nginx-alpine`
     - Tag: `nginx-alpine:latest`
     - Usado pelo `setupAll.bat` para configurar todas as versões de uma vez

## Nomenclatura dos Containers e Imagens

### Apache (Servidor + Aplicação)
- **Container**: `php-{PHP_VERSION}-apache` (ex: `php-8.3-apache`)
- **Imagem**: `php-{PHP_VERSION}-apache` (ex: `php-8.3-apache`)
- **Tag**: `php:{PHP_VERSION}-apache` (ex: `php:8.3-apache`)

### Nginx (Servidor)
- **Container unificado**: `nginx-unified` (para uso com `setupAll.bat`)
- **Container específico**: `nginx-{PHP_VERSION}` (ex: `nginx-8.3`, para uso com `setup.bat`)
- **Imagem**: `nginx-alpine`
- **Tag**: `nginx-alpine:latest`

### PHP-FPM (Aplicação)
- **Container**: `php-{PHP_VERSION}-fpm` (ex: `php-8.3-fpm`)
- **Imagem**: `php-{PHP_VERSION}-fpm` (ex: `php-8.3-fpm`)
- **Tag**: `php:{PHP_VERSION}-fpm` (ex: `php:8.3-fpm`)

## Como usar setup.bat individualmente

### Windows
```batch
.\setup.bat --build-arg PHP_VERSION=8.3 --run "docker compose up -d app nginx"
```

### Linux/MacOS
```bash
./setup.sh --build-arg PHP_VERSION=8.3 --run "docker compose up -d app nginx"
```

**IMPORTANTE**: Note que o comando usa `app` e `nginx` - o `setup.bat` converte ambos para serviços específicos:
- `app` → `php-{PHP_VERSION}-fpm` (ex: `php-8.3-fpm`)
- `nginx` → `nginx-{PHP_VERSION}` (ex: `nginx-8.3`)

## Como funciona

1. O `setup.bat` atualiza o arquivo `.env.docker` e copia para `.env`
2. O `setup.bat` **automaticamente converte** `apache`, `app` e `nginx` para serviços específicos no comando `--run`:
   - `apache` → `php-{PHP_VERSION}-apache`
   - `app` → `php-{PHP_VERSION}-fpm`
   - `nginx` → `nginx-{PHP_VERSION}`
3. O Docker Compose usa os serviços específicos convertidos para construir e iniciar os containers
4. Cada versão PHP cria seu próprio container independente (PHP-FPM, Apache e Nginx), permitindo múltiplas versões simultaneamente sem conflitos

**✅ IMPORTANTE sobre o uso do `setup.bat`:**
- O `setup.bat` **automaticamente converte** serviços genéricos `apache`, `app` e `nginx` para serviços específicos baseado na versão PHP informada
- Isso permite usar múltiplas versões simultaneamente sem conflitos - cada versão tem seus próprios containers independentes
- Exemplo: `.\setup.bat ... --run "docker compose up -d apache"` é convertido para `docker compose up -d php-8.0-apache`
- Exemplo: `.\setup.bat ... --run "docker compose up -d app nginx"` é convertido para `docker compose up -d php-8.3-fpm nginx-8.3`
- Cada versão PHP cria seu próprio container independente (PHP-FPM, Apache e Nginx)

## Exemplos de uso individual

### PHP 8.0 com Nginx
```batch
# Windows
.\setup.bat --build-arg PHP_VERSION=8.0 --run "docker compose up -d app nginx"

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.0 --run "docker compose up -d app nginx"
```

### PHP 8.1 com Nginx
```batch
# Windows
.\setup.bat --build-arg PHP_VERSION=8.1 --run "docker compose up -d app nginx"

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.1 --run "docker compose up -d app nginx"
```

### PHP 8.2 com Nginx
```batch
# Windows
.\setup.bat --build-arg PHP_VERSION=8.2 --run "docker compose up -d app nginx"

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.2 --run "docker compose up -d app nginx"
```

### PHP 8.3 com Nginx
```batch
# Windows
.\setup.bat --build-arg PHP_VERSION=8.3 --run "docker compose up -d app nginx"

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.3 --run "docker compose up -d app nginx"
```

### PHP 8.4 com Nginx
```batch
# Windows
.\setup.bat --build-arg PHP_VERSION=8.4 --run "docker compose up -d app nginx"

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.4 --run "docker compose up -d app nginx"
```

### PHP 8.5 com Nginx
```batch
# Windows
.\setup.bat --build-arg PHP_VERSION=8.5 --run "docker compose up -d app nginx"

# Linux/MacOS
./setup.sh --build-arg PHP_VERSION=8.5 --run "docker compose up -d app nginx"
```

### PHP com Apache (individual)

**O `setup.bat` converte automaticamente `apache` para `php-{PHP_VERSION}-apache`:**
```batch
# Windows - PHP 8.0
.\setup.bat --build-arg PHP_VERSION=8.0 --run "docker compose up -d apache"
# Comando executado: docker compose up -d php-8.0-apache

# Windows - PHP 8.1
.\setup.bat --build-arg PHP_VERSION=8.1 --run "docker compose up -d apache"
# Comando executado: docker compose up -d php-8.1-apache

# Linux/MacOS - PHP 8.3
./setup.sh --build-arg PHP_VERSION=8.3 --run "docker compose up -d apache"
# Comando executado: docker compose up -d php-8.3-apache
```

**✅ Resultado**: Cada versão cria seu próprio container independente. Você pode executar múltiplas versões simultaneamente sem conflitos!

## Como usar setupAll.bat

### Windows
```batch
# Nginx (unificado)
.\setupAll.bat

# Apache
.\setupAll.bat --apache
```

### Linux/MacOS
```bash
# Nginx (unificado)
./setupAll.sh

# Apache
./setupAll.sh --apache
```

O `setupAll.bat` configura todas as versões PHP (8.0 a 8.5) de uma vez:
- **Com Nginx**: Inicia todos os serviços PHP-FPM (app80-app85) + servidor Nginx unificado
- **Com Apache**: Inicia todos os serviços Apache (apache80-apache85)

## Usando comandos Docker diretamente

### Listar containers em execução
```bash
# Todos os containers
docker ps

# Apenas containers PHP
docker ps | grep php-

# Apenas container Nginx
docker ps | grep nginx
```

### Inspecionar containers

#### PHP-FPM
```bash
# PHP 8.3 FPM
docker inspect php-8.3-fpm

# Ver logs
docker logs php-8.3-fpm

# Executar comandos dentro do container
docker exec -it php-8.3-fpm php -v
docker exec -it php-8.3-fpm composer --version
```

#### Apache
```bash
# PHP 8.3 Apache
docker inspect php-8.3-apache

# Ver logs
docker logs php-8.3-apache

# Executar comandos dentro do container
docker exec -it php-8.3-apache php -v
docker exec -it php-8.3-apache apache2ctl -v
```

#### Nginx
```bash
# Nginx unificado
docker inspect nginx-unified

# Ver logs
docker logs nginx-unified

# Testar configuração
docker exec -it nginx-unified nginx -t

# Recarregar configuração
docker exec -it nginx-unified nginx -s reload

# Nginx específico (para uso individual)
docker inspect nginx-8.3

# Ver logs
docker logs nginx-8.3

# Testar configuração
docker exec -it nginx-8.3 nginx -t

# Recarregar configuração
docker exec -it nginx-8.3 nginx -s reload
```

### Listar imagens
```bash
# Todas as imagens
docker images

# Apenas imagens PHP
docker images | grep php-

# Apenas imagens Nginx
docker images | grep nginx-alpine
```

### Construir imagens manualmente

#### PHP-FPM
```bash
# PHP 8.3 FPM
docker compose build php-8.3-fpm

# Ou diretamente com docker build
docker build -f docker/php-fpm/Dockerfile \
  --build-arg PHP_VERSION=8.3 \
  --build-arg PHP_PORT=8300 \
  --build-arg PHP_BASE_TAG=-bookworm \
  -t php-8.3-fpm \
  .
```

#### Apache
```bash
# PHP 8.3 Apache (serviço específico - recomendado)
docker compose build php-8.3-apache

# PHP 8.1 Apache (serviço específico - recomendado)
docker compose build php-8.1-apache

# Ou diretamente com docker build
docker build -f docker/php/Dockerfile \
  --build-arg PHP_VERSION=8.3 \
  --build-arg PHP_PORT=8300 \
  --build-arg PHP_BASE_TAG=-bookworm \
  -t php-8.3-apache \
  .
```

#### Nginx
```bash
# Nginx unificado
docker compose build nginx-unified

# Ou diretamente com docker build
docker build -f docker/nginx/Dockerfile \
  -t nginx-alpine:latest \
  .
```

### Iniciar/Parar containers manualmente

#### Usando Docker Compose (recomendado)
```bash
# Iniciar serviços específicos (recomendado - permite múltiplas versões)
# PHP-FPM + Nginx
docker compose up -d php-8.0-fpm php-8.1-fpm php-8.2-fpm php-8.3-fpm php-8.4-fpm php-8.5-fpm nginx-unified

# Apache (versões específicas)
docker compose up -d php-8.1-apache php-8.3-apache

# Ou todos os Apache
docker compose up -d php-8.0-apache php-8.1-apache php-8.2-apache php-8.3-apache php-8.4-apache php-8.5-apache

# Parar serviços específicos
docker compose stop php-8.0-fpm php-8.1-fpm php-8.2-fpm nginx-unified
docker compose stop php-8.0-apache php-8.1-apache php-8.2-apache

# Remover containers (mantém volumes)
docker compose rm -f php-8.0-fpm php-8.1-fpm php-8.2-fpm
docker compose rm -f php-8.0-apache php-8.1-apache php-8.2-apache

# NOTA: O setup.bat automaticamente converte serviços genéricos para específicos
# Você pode usar 'apache' no comando e ele será convertido para 'php-{PHP_VERSION}-apache'
```

#### Usando Docker diretamente
```bash
# Iniciar container PHP-FPM 8.3
docker run -d --name php-8.3-fpm \
  --network internal \
  -v $(pwd):/var/www/html \
  php-8.3-fpm

# Iniciar container Apache 8.3
docker run -d --name php-8.3-apache \
  --network internal \
  -p 8300:80 -p 8343:443 \
  -v $(pwd):/var/www/html \
  php-8.3-apache

# Parar container
docker stop php-8.3-fpm
docker stop php-8.3-apache

# Remover container
docker rm php-8.3-fpm
docker rm php-8.3-apache
```

### Remover imagens
```bash
# Remover imagem específica
docker rmi php-8.3-fpm
docker rmi php-8.3-apache
docker rmi nginx-alpine:latest

# Remover todas as imagens PHP-FPM
docker images | grep php-.*-fpm | awk '{print $3}' | xargs docker rmi

# Remover todas as imagens Apache
docker images | grep php-.*-apache | awk '{print $3}' | xargs docker rmi
```

## Diferenças entre setup.bat e setupAll.bat

| Aspecto | setup.bat (individual) | setupAll.bat |
|---------|------------------------|--------------|
| **Serviço usado** | `app`/`apache`/`nginx` (genérico) | `php-8.0-fpm` até `php-8.5-fpm`/`php-8.0-apache` até `php-8.5-apache`/`nginx-unified` (específicos) |
| **Variáveis** | Lê do `.env` | Valores hardcoded no docker-compose.yml |
| **Uso** | Uma versão por vez | Todas as versões de uma vez |
| **Porta** | Definida via argumento | Fixa por serviço (8000, 8100, etc.) |
| **Container name** | `php-{PHP_VERSION}-fpm` ou `php-{PHP_VERSION}-apache` | `php-{PHP_VERSION}-fpm` ou `php-{PHP_VERSION}-apache` |
| **Nginx container** | `nginx-{PHP_VERSION}` (específico por versão) | `nginx-unified` (unificado) |

## Verificação

Após executar o `setup.bat` individual, você pode verificar:

1. **Container em execução**:
   ```bash
   # Windows
   docker ps | findstr php-
   
   # Linux/MacOS
   docker ps | grep php-
   ```

2. **Acessar a aplicação**:
   - URL: `http://localhost:PORTA` (ex: http://localhost:8300 para PHP 8.3)
   - HTTPS: `https://localhost:PORTA_SSL` (ex: https://localhost:8343 para PHP 8.3)

3. **Verificar versão do PHP**:
   ```bash
   docker exec php-8.3-fpm php -v
   docker exec php-8.3-apache php -v
   ```

4. **Verificar se Nginx está rodando**:
   ```bash
   docker ps | grep nginx
   docker exec nginx nginx -v
   ```

## Notas Importantes

### Comportamento Automático do setup.bat

**Conversão Automática de Serviços:**
- O `setup.bat` e `setup.sh` **automaticamente convertem** serviços genéricos `apache`, `app` e `nginx` para específicos no comando `--run`
- Exemplos de conversão:
  - `docker compose up -d apache` → `docker compose up -d php-8.0-apache` (se PHP_VERSION=8.0)
  - `docker compose up -d app nginx` → `docker compose up -d php-8.1-fpm nginx-8.1` (se PHP_VERSION=8.1)
  - `docker compose up -d app` → `docker compose up -d php-8.3-fpm` (se PHP_VERSION=8.3)
  - `docker compose up -d nginx` → `docker compose up -d nginx-8.3` (se PHP_VERSION=8.3)

**Vantagens:**
- Permite usar múltiplas versões simultaneamente sem conflitos
- Cada versão PHP cria seu próprio container independente
- Não recria containers de outras versões
- Sintaxe simples: continue usando `apache`, `app`, `nginx` no comando, o script faz a conversão

**Serviços Específicos Diretos:**
- Você também pode usar os serviços específicos diretamente: `docker compose up -d php-8.1-apache`
- Útil quando precisar de mais controle ou usar fora do `setup.bat`

### Outras Notas

- O `setup.bat` automaticamente converte serviços genéricos `apache` e `app` para específicos no comando `--run`
- O `setup.bat` automaticamente converte serviços genéricos `apache`, `app` e `nginx` para específicos no comando `--run`
- Isso permite usar múltiplas versões simultaneamente sem conflitos - cada versão cria seus próprios containers independentes (PHP-FPM, Apache e Nginx)
- Para usar múltiplas versões de uma vez, você pode executar `setup.bat` várias vezes com diferentes versões, ou usar `setupAll.bat`
- Os serviços genéricos `app`, `apache` e `nginx` foram removidos do docker-compose.yml para evitar conflitos com serviços específicos
- Para uso direto via `docker compose`, use os serviços específicos: `docker compose up -d php-8.3-apache` ou `docker compose up -d php-8.3-fpm nginx-8.3`
- O servidor Nginx unificado (`nginx-unified`) serve todas as versões PHP através de portas diferentes (8000-8500 HTTP, 8043-8543 HTTPS)
- Cada porta do Nginx unificado roteia para o serviço PHP-FPM correspondente (php-8.0-fpm, php-8.1-fpm, etc.)
- **IMPORTANTE**: Com as mudanças, agora cada versão PHP tem seu próprio container Nginx quando usando `setup.bat`, permitindo múltiplas versões simultâneas sem conflitos de portas
